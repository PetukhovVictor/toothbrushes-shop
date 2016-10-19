<?php namespace BackController;

require_once SYS_DIR . "/config.php";
require_once STORAGE_DIR . "/database/mysql.php";
require_once STORAGE_DIR . "/cache/memcached.php";

const CONSTRAINS = array(
    "page" => '/^[1-9]\d*?$/'
);

const ITEMS_CACHE_SIZE = 10000; // 10.000 items per
const ITEMS_CACHE_PORTION_SIZE = 1000;
const ITEMS_CACHE_FIELD_KEY = "id";
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

function cachePortion($resultSet, &$itemIds) {
    $id_prev_set = 0;
    foreach ($resultSet as $row) {
        array_push($itemIds, $row["id"]);
        $item = array();
        foreach (ITEMS_CACHE_FIELDS as $field) {
            $item[$field] = $row[$field];
        }
        \Memcached\add("catalog/item/" . $row["id"], $item);
        $id_prev_set = $row["id"];
    }
    return $id_prev_set;
}

function cacheAll($limit) {
    $cacheItemNumber = ceil($limit / ITEMS_CACHE_SIZE);
    $itemIds = array();
    $isContinueRequestData = true;
    $page = 1;
    $idPrevSet = 0;
    while ($isContinueRequestData) {
        $time = microtime();
        $resultSet = \DB\query("SELECT * FROM Items WHERE id > {$idPrevSet} ORDER BY id ASC LIMIT 0," . ITEMS_CACHE_PORTION_SIZE, \DB\SELECT_QUERY);
        echo "SELECT * FROM Items WHERE id > {$idPrevSet} ORDER BY id ASC LIMIT {$limit[0]},{$limit[1]} <br/>";
        echo ((microtime() - $time)*1000) . "<br />";
        if (count($resultSet) == 0) {
            break;
        }
        $idPrevSet = cachePortion($resultSet, $itemIds); // itemIds by reference because may be big array
        if ($page == 20) {
            $isContinueRequestData = false;
        }
        $page++;
    }
    \Memcached\add("catalog/itemsById", $itemIds);
}

function checkCached($page, $sorting) {
    $ids = \Memcached\get("catalog/items{$sorting}");
    if ($ids === false) {
        return false;
    }

}

function loadItems($page, $items_one_page) {
    cacheAll(500);
    exit();
    $inChache = checkCached($page, "ById");
    $limit = calcLimitSet($page, $items_one_page);
    $items = \DB\query("SELECT * FROM Items ORDER BY id DSEC LIMIT {$limit[0]},{$limit[1]}", \DB\SELECT_QUERY);
    return $items;
}