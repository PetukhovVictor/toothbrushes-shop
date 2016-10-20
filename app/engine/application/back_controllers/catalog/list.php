<?php namespace BackController;

require_once SYS_DIR . "/config.php";
require_once STORAGE_DIR . "/database/mysql.php";
require_once STORAGE_DIR . "/cache/memcached.php";
require_once SYS_DIR . "/data_structures/RBTree.php";

const CONSTRAINS = array(
    "page" => '/^[1-9]\d*?$/'
);

const CACHE_SNAPSHOTS_DIRECTORY = HOME_DIR . "/../../data/cache_snapshots";
const ITEMS_CACHE_FIELDS = array("title", "price", "image");

function paramsCheck($params) {
    if (!empty($params["page"]) && !preg_match(CONSTRAINS["page"], $params["page"])) {
        return false;
    }
    return true;
}

function calcLimitSet($page, $items_one_page) {
    return array(($page - 1) * $items_one_page, $items_one_page);
}

function getItemsTree($sorting) {
    $tree = \Memcached\get("catalog/items/treeBy{$sorting}");
    if ($tree === false) { // cache miss
        $tree = file_get_contents(CACHE_SNAPSHOTS_DIRECTORY . "/catalog/items/treeBy{$sorting}.data");
        $tree = unserialize($tree);
        \Memcached\add("catalog/items/treeBy{$sorting}", $tree);
    }
    return $tree;
}

function getSectorNumbers(&$itemsTree, $limit) {
    $sectorFirst = $itemsTree->lastBest($itemsTree, $limit[0] + 1);
    $sectorLast = $itemsTree->lastBest($itemsTree, $limit[0] + 1 + $limit[1] + 1);
    return array(
        "first" => array(
            "leftBound" => $sectorFirst->key,
            "sector" => $sectorFirst->value
        ),
        "last" => array(
            "leftBound" => $sectorLast->key,
            "sector" => $sectorLast->value
        )
    );
}

function getIds($sectors, $limit, $sorting) {
    $itemsResidue = $limit[1];
    $itemIds = array();
    $leftBound = ($limit[0] + 1) - $sectors["first"]["leftBound"];
    for ($i = $sectors["first"]["sector"]; $i <= $sectors["last"]["sector"]; $i++) {
        $itemIdsSector = \Memcached\get("catalog/items/arrayBy{$sorting}/{$i}");
        if ($itemIdsSector === false) { // cache miss
            $itemIdsSector = file_get_contents(CACHE_SNAPSHOTS_DIRECTORY . "/catalog/items/arrayBy{$sorting}/{$i}.data");
            $itemIdsSector = unserialize($itemIdsSector);
            \Memcached\add("catalog/items/arrayBy{$sorting}/{$i}", $itemIdsSector);
        }
        $itemIds = array_merge($itemIds, array_slice($itemIdsSector, $leftBound, $itemsResidue));
        $leftBound = 0;
        $itemsResidue = $limit[1] - count($itemIds);
        if ($itemsResidue == 0) {
            break;
        }
    }
    return $itemIds;
}

function getItems($itemIds) {
    $items = array();
    for ($i = 0; $i < count($itemIds); $i++) {
        $item = \Memcached\get("catalog/item/{$itemIds[$i]}");
        if ($item === false) { // cache miss
            $fields = implode(",", ITEMS_CACHE_FIELDS);
            $item = \DB\query("SELECT {$fields} FROM Items WHERE id = '{$itemIds[$i]}'", \DB\SELECT_QUERY);
            $item = $item[0];
            \Memcached\add("catalog/item/{$itemIds[$i]}", $item);
        }
        $item["id"] = $itemIds[$i];
        array_push($items, $item);
    }
    return $items;
}

function loadItems($page, $items_one_page) {
    $limit = calcLimitSet($page, $items_one_page);
    $itemsTree = getItemsTree("id");
    $sectors = getSectorNumbers($itemsTree, $limit);
    $itemIds = getIds($sectors, $limit, "id");
    $items = getItems($itemIds);
    return $items;
}