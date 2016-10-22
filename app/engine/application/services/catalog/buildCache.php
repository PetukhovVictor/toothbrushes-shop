<?php namespace Service;

define("HOME_DIR", dirname(__FILE__) . "/../../../..");
define("SYS_DIR", HOME_DIR . "/engine/system");
define("STORAGE_DIR", SYS_DIR . "/storage");

require_once SYS_DIR . "/config.php";
require_once STORAGE_DIR . "/database/mysql.php";
require_once STORAGE_DIR . "/cache/memcached.php";
require_once SYS_DIR . "/data_structures/RBTree.php";

const CACHE_SNAPSHOTS_DIRECTORY = HOME_DIR . "/../data/cache_snapshots";
const ITEMS_CACHE_SIZE = 10000; // 10.000 items per one key
const ITEMS_CACHE_SQL_PORTION_SIZE = 1000; // must be a multiple of ITEMS_CACHE_SIZE
const ITEMS_CACHE_FIELD_KEY = "id";
const ITEMS_CACHE_FIELDS = array("title", "price", "image");

function cacheSnapshot($path, $key, $value) {
    $path = explode("/", $path);
    $currentDir = CACHE_SNAPSHOTS_DIRECTORY;
    foreach($path as $dir) {
        $currentDir = $currentDir . "/" . $dir;
        if (!is_dir($currentDir)) {
            mkdir($currentDir);
        }
    }
    file_put_contents($currentDir . "/" . $key . ".data", serialize($value));
}

function cacheItems(&$resultSet, &$itemIds, $option) {
    $idPrevSet = 0;
    $fieldPrevSet = 0;
    foreach ($resultSet as $row) {
        $itemIndex = array_push($itemIds, $row["id"]) - 1;
        $idPrevSet = $row["id"];
        $fieldPrevSet = $row[$option["field"]];
        $item = array();
        foreach (ITEMS_CACHE_FIELDS as $field) {
            $item[$field] = $row[$field];
        }
        $item["sectors"] = array(
            "by_" . $option["field"] => array(
                "number" => $option["sectorNumber"],
                "index" => $itemIndex
            )
        );
        $currentItem = \Memcached\get("catalog/item/" . $row["id"]);
        if ($currentItem === false) {
            \Memcached\add("catalog/item/" . $row["id"], $item);
        } else {
            $item["sectors"] = array_merge($currentItem["sectors"], $item["sectors"]);
            \Memcached\set("catalog/item/" . $row["id"], $item);
        }
        \DB\query("INSERT INTO ItemsCacheSectors(`item_id`, `type`, `sector`, `index`) VALUES(:item_id, :type, :sector, :index)", \DB\INSERT_QUERY, array(
            "item_id" => $row["id"],
            "type" => $option["field"],
            "sector" => $option["sectorNumber"],
            "index" => $itemIndex
        ));
    }
    return array(
        "id" => $idPrevSet,
        "field" => $fieldPrevSet
    );
}

function cacheIds(&$itemIds, &$sectorBoundsTree, &$sectorsArray, $option) {
    $sectorLeftBound = $option["lastRowNumberPrevSector"] + 1;
    $sectorNode = new \RbNode();
    $sectorNode->key = $sectorLeftBound;
    $sectorNode->value = $option["sectorNumber"];
    $sectorBoundsTree->insert($sectorBoundsTree, $sectorNode);
    $newSectorNumber = array_push($sectorsArray, $sectorLeftBound) + 1;
    \Memcached\add("catalog/items/ids/by_" . $option["field"] . "/" . $option["sectorNumber"], $itemIds);
    cacheSnapshot("catalog/items/ids/by_" . $option["field"], $option["sectorNumber"], $itemIds);
    return $newSectorNumber;
}

function cacheSectors($field, &$sectorsArray, &$sectorBoundsTree) {
    \Memcached\add("catalog/items/sectors/by_" . $field . "/bounds_tree", $sectorBoundsTree);
    cacheSnapshot("catalog/items/sectors/by_" . $field, "bounds_tree", $sectorBoundsTree);
    \Memcached\add("catalog/items/sectors/by_" . $field . "/array", $sectorsArray);
    cacheSnapshot("catalog/items/sectors/by_" . $field, "array", $sectorsArray);
}

function getNumberItems() {
    $numberAllItemResult = \DB\query("SELECT COUNT(*) as count FROM `Items`", \DB\SELECT_QUERY);
    return $numberAllItemResult[0]["count"];
}

function cache($field) {
    fprintf(STDOUT, "Caching by %s element started...\n", ITEMS_CACHE_SIZE);
    fprintf(STDOUT, "------------------------------\n");
    $itemIds = array();
    $sectorBoundsTree = new \RbTree();
    $sectorsArray = array();
    $prevValue = array(
        "id" => 0,
        "field" => 0
    );
    $sectorNumber = 1;
    $numberItems = 0;
    $lastRowNumberPrevSector = 0;
    $numberAllItem = getNumberItems();
    $numberAllSectors = ceil($numberAllItem / ITEMS_CACHE_SIZE);
    $time = microtime(true);
    while (true) {
        $where = $field != ITEMS_CACHE_FIELD_KEY
            ? "(id > {$prevValue["id"]} AND {$field} = {$prevValue["field"]}) OR {$field} > {$prevValue["field"]}"
            : "id > {$prevValue["id"]}";
        $orderBy = $field != ITEMS_CACHE_FIELD_KEY
            ? "{$field} ASC, " . ITEMS_CACHE_FIELD_KEY . " ASC"
            : ITEMS_CACHE_FIELD_KEY . " ASC";
        $resultSet = \DB\query("SELECT * FROM Items WHERE {$where} ORDER BY {$orderBy} LIMIT 0," . ITEMS_CACHE_SQL_PORTION_SIZE, \DB\SELECT_QUERY);
            // need complex index in mySQL (${field} + ${ITEMS_CACHE_FIELD_KEY})
        if (count($resultSet) == 0 && count($itemIds) == 0) {
            break;
        }
        $numberItems += count($resultSet);
        if (count($resultSet) != 0) {
            $prevValue = cacheItems($resultSet, $itemIds, array(
                "field" => $field,
                "sectorNumber" => $sectorNumber
            )); // itemIds and resultSet by reference because may be big
        }
        if (count($itemIds) >= ITEMS_CACHE_SIZE || count($resultSet) == 0) {
            $newSectorNumber = cacheIds($itemIds, $sectorBoundsTree, $sectorsArray, array(
                "sectorNumber" => $sectorNumber,
                "field" => $field,
                "lastRowNumberPrevSector" => $lastRowNumberPrevSector
            ));
            $lastRowNumberPrevSector += count($itemIds);
            fprintf(STDOUT, "Part %d of %d cached in %.3f ms (%s: ...%d...)\n", $sectorNumber, $numberAllSectors, (microtime(true) - $time) * 1000, $field, $prevValue["field"]);
            unset($itemIds);
            $itemIds = array();
            $sectorNumber = $newSectorNumber;
            $time = microtime(true);
        }
    }
    cacheSectors($field, $sectorsArray, $sectorBoundsTree);
    return array(
        "items" => $numberItems,
        "sectors" => $sectorNumber - 1
    );
}

function cacheStatsPrint($numberCache, $startTime, $field) {
    $itemsNumberForSectorsStore = 2;
    $memcachedStats = \Memcached\stats();
    fprintf(STDOUT, "------------------------------\n");
    fprintf(STDOUT, "Cache built by {$field} in %.3f minutes\n", (microtime(true) - $startTime) / 60);
    fprintf(STDOUT, "Items in cache: %d (including %d for ids store and %d for sectors store)\n", $numberCache["items"] + $numberCache["sectors"] + $itemsNumberForSectorsStore, $numberCache["sectors"], $itemsNumberForSectorsStore);
    fprintf(STDOUT, "Current cache size: %.3f MB\n", $memcachedStats["bytes"] / 1024 / 1024);
    fprintf(STDOUT, "------------------------------\n");
}

if (empty($argv[1])) {
    fprintf(STDOUT, "Caching field not specified.\n");
}

$time = microtime(true);
$numberCache = cache($argv[1]);
cacheStatsPrint($numberCache, $time, $argv[1]);