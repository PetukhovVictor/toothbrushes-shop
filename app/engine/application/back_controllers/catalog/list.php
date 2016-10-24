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

function getSectorBoundsTree($orderBy) {
    $tree = \Memcached\get("catalog/items/sectors/by_{$orderBy}/bounds_tree");
    if ($tree === false) { // cache miss
        $tree = file_get_contents(CACHE_SNAPSHOTS_DIRECTORY . "/catalog/items/sectors/by_{$orderBy}/bounds_tree.data");
        $tree = unserialize($tree);
        \Memcached\add("catalog/items/sectors/by_{$orderBy}/bounds_tree", $tree);
    }
    return $tree;
}

function choiceSector(&$sectorBoundsTree, $limit, $orderDirection) {
    if ($orderDirection == "desc") {
        $catalogInfo = \Memcached\get("catalog/items/common/info");
        if ($catalogInfo === false) {
            $catalogInfo = file_get_contents(CACHE_SNAPSHOTS_DIRECTORY . "/catalog/items/common/info.data");
            $catalogInfo = unserialize($catalogInfo);
            \Memcached\add("catalog/items/common/info", $catalogInfo);
        }
        $numberAllItems = $catalogInfo["numberAllItem"];
        $originalLimit1 = $limit[1];
        $limit[1] = $originalLimit1;
        $limit[0] = $numberAllItems - $limit[0] - $originalLimit1;
    }
    $sectorFirst = $sectorBoundsTree->lastBest($sectorBoundsTree, $limit[0] + 1);
    $sectorLast = $sectorBoundsTree->lastBest($sectorBoundsTree, $limit[0] + 1 + $limit[1] + 1);
    return array(
        "limit" => $limit,
        "first" => array(
            "bound" => $sectorFirst->key,
            "number" => $sectorFirst->value
        ),
        "last" => array(
            "bound" => $sectorLast->key,
            "number" => $sectorLast->value
        )
    );
}

function getItemIds($sectors, $limit, $orderBy) {
    $itemsResidue = $limit[1];
    $itemIds = array();
    $leftBound = ($limit[0] + 1) - $sectors["first"]["bound"];
    for ($i = $sectors["first"]["number"]; $i <= $sectors["last"]["number"]; $i++) {
        $itemIdsSector = \Memcached\get("catalog/items/ids/by_{$orderBy}/{$i}");
        if ($itemIdsSector === false) { // cache miss
            $itemIdsSector = file_get_contents(CACHE_SNAPSHOTS_DIRECTORY . "/catalog/items/ids/by_{$orderBy}/{$i}.data");
            $itemIdsSector = unserialize($itemIdsSector);
            \Memcached\add("catalog/items/ids/by_{$orderBy}/{$i}", $itemIdsSector);
        }
        array_push($itemIds, array(
            "offset" => $leftBound,
            "items" => array_slice($itemIdsSector, $leftBound, $itemsResidue)
        ));
        $leftBound = 0;
        $itemsResidue = $limit[1] - count($itemIds);
        if ($itemsResidue == 0) {
            break;
        }
    }
    return $itemIds;
}

function getItems($itemIds, $orderBy, $orderDirection, $chosenSectors) {
    $items = array();
    for($i = 0; $i < count($itemIds); $i++) {
        $sector = $itemIds[$i];
        for($j = 0; $j < count($sector["items"]); $j++) {
            $id = $sector["items"][$j];
            $item = \Memcached\get("catalog/item/{$id}");
            if ($item === false) { // cache miss
                $fields = implode(",", ITEMS_CACHE_FIELDS);
                $item = \DB\query("SELECT {$fields} FROM Items WHERE id = '{$id}'", \DB\SELECT_QUERY);
                $item = $item[0];
                $item["sectors"] = array(
                    "by_" . $orderBy => array(
                        "number" => $chosenSectors["first"]["number"],
                        "index" => $sector["offset"] + $j
                    )
                );
                \Memcached\add("catalog/item/{$id}", $item);
            } else if (empty($item["sectors"]["by_" . $orderBy])) {
                $item["sectors"]["by_" . $orderBy] = array(
                    "number" => $chosenSectors["first"]["number"],
                    "index" => $sector["offset"] + $j
                );
                \Memcached\set("catalog/item/{$id}", $item);
            }
            $item["price"] = number_format($item["price"], 0, '.', ' ');
            $item["id"] = $id;
            unset($item["sectors"]);
            array_push($items, $item);
        }
    }
    if ($orderDirection == "desc") {
        $items = array_reverse($items);
    }
    return $items;
}

function loadItems($page, $orderBy, $orderDirection, $items_one_page) {
    $limit = calcLimitSet($page, $items_one_page);
    $sectorBoundsTree = getSectorBoundsTree($orderBy);
    $sectors = choiceSector($sectorBoundsTree, $limit, $orderDirection);
    $itemIds = getItemIds($sectors, $sectors["limit"], $orderBy);
    $items = getItems($itemIds, $orderBy, $orderDirection, $sectors);
    return $items;
}