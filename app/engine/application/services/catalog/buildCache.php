<?php namespace Service;

define("HOME_DIR", dirname(__FILE__) . "/../../..");
define("SYS_DIR", HOME_DIR . "/../engine/system");
define("STORAGE_DIR", SYS_DIR . "/storage");

require_once SYS_DIR . "/config.php";
require_once STORAGE_DIR . "/database/mysql.php";
require_once STORAGE_DIR . "/cache/memcached.php";

const ITEMS_CACHE_SIZE = 10000; // 10.000 items per one key
const ITEMS_CACHE_SQL_PORTION_SIZE = 1000;
const ITEMS_CACHE_FIELD_KEY = "id";
const ITEMS_CACHE_FIELDS = array("title", "price", "image");

function cacheItem(&$resultSet, &$itemIds, $criteria) {
    $idPrevSet = 0;
    $criteriaPrevSet = 0;
    foreach ($resultSet as $row) {
        array_push($itemIds, $row["id"]);
        $idPrevSet = $row["id"];
        $criteriaPrevSet = $row[$criteria];
        if (\Memcached\get("catalog/item/" . $row["id"]) !== false) {
            continue;
        }
        $item = array();
        foreach (ITEMS_CACHE_FIELDS as $field) {
            $item[$field] = $row[$field];
        }
        \Memcached\add("catalog/item/" . $row["id"], $item);
    }
    return array(
        "id" => $idPrevSet,
        "criteria" => $criteriaPrevSet
    );
}

function cacheIds(&$itemIds, $numberPortionIds, $criteria) {
    if (\Memcached\get("catalog/itemsBy" . $criteria . "/" . $numberPortionIds) !== false) {
        return;
    }
    \Memcached\add("catalog/itemsBy" . $criteria . "/" . $numberPortionIds, $itemIds);
}

function getNumberItems() {
    $numberAllItemResult = \DB\query("SELECT COUNT(*) as count FROM `Items`", \DB\SELECT_QUERY);
    return $numberAllItemResult[0]["count"];
}

function cache($criteria) {
    fprintf(STDOUT, "Caching by %s element started...\n", ITEMS_CACHE_SIZE);
    fprintf(STDOUT, "------------------------------\n");
    $itemIds = array();
    $prevSet = array(
        "id" => 0,
        "criteria" => 0
    );
    $numberPortionIds = 1;
    $numberItems = 0;
    $i = 0;
    $numberAllItem = getNumberItems();
    $numberAllPortions = ceil($numberAllItem / ITEMS_CACHE_SIZE);
    $time = microtime(true);
    while (true) {
        $where = $criteria != ITEMS_CACHE_FIELD_KEY ? "(id > {$prevSet["id"]} AND {$criteria} = {$prevSet["criteria"]}) OR {$criteria} > {$prevSet["criteria"]}" : "id > {$prevSet["id"]}";
        $resultSet = \DB\query("SELECT * FROM Items WHERE {$where} ORDER BY {$criteria} ASC, " . ITEMS_CACHE_FIELD_KEY . " ASC LIMIT 0," . ITEMS_CACHE_SQL_PORTION_SIZE, \DB\SELECT_QUERY);
            // need complex index in mySQL (${criteria} + ${ITEMS_CACHE_FIELD_KEY})
        if (count($resultSet) == 0 && count($itemIds) == 0) {
            break;
        }
        $numberItems += count($resultSet);
        if (count($resultSet) != 0) {
            $prevSet = cacheItem($resultSet, $itemIds, $criteria); // itemIds and resultSet by reference because may be big
        }
        if (count($itemIds) > ITEMS_CACHE_SIZE || count($resultSet) == 0) {
            cacheIds($itemIds, $numberPortionIds, $criteria);
            fprintf(STDOUT, "Part %d of %d cached in %.3f ms (%s: ...%d...)\n", $numberPortionIds, $numberAllPortions, (microtime(true) - $time) * 1000, $criteria, $prevSet["criteria"]);
            unset($itemIds);
            $itemIds = array();
            $numberPortionIds++;
            $time = microtime(true);
        }
        $i++;
        if ($i == -1) {
            break;
        }
    }
    return array(
        "items" => $numberItems,
        "portions" => $numberPortionIds - 1
    );
}

function cacheStatsPrint($numberCache, $startTime, $criteria) {
    $memcachedStats = \Memcached\stats();
    fprintf(STDOUT, "------------------------------\n");
    fprintf(STDOUT, "Cache built by {$criteria} in %.3f minutes\n", (microtime(true) - $startTime) / 60);
    fprintf(STDOUT, "Items in cache: %d (+%d additional items for ids store)\n", $numberCache["items"], $numberCache["portions"]);
    fprintf(STDOUT, "Current cache size: %.3f MB\n", $memcachedStats["bytes"] / 1024 / 1024);
    fprintf(STDOUT, "------------------------------\n");
}

if (empty($argv[1])) {
    fprintf(STDOUT, "Caching criteria not specified.\n");
}

$time = microtime(true);
$numberCache = cache($argv[1]);
cacheStatsPrint($numberCache, $time, $argv[1]);