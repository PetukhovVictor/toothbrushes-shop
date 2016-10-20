<?php namespace BackController;

require_once SYS_DIR . "/config.php";
require_once STORAGE_DIR . "/database/mysql.php";
require_once STORAGE_DIR . "/cache/memcached.php";

const CONSTRAINS = array(
    "id" => '^[1-9]\d*?$',
    "title" => '^.{1,255}$',
    "description" => '^.{1,65535}$',
    "price" => '^[+-]([0-9]*[.])?[0-9]+$',
    "image" => '^http:\/\/[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+$'
);

const ARRAYS_ID_MAX_DEFLECTION_FACTOR = 0.9; // d factor = (actual size - predefined size) / actual size
const ITEMS_CACHE_FIELDS = array("title", "price", "image");

function paramsCheck($params, $mode = "add") {
    if (empty($params) || !is_array($params)) {
        return false;
    }
    if ($mode == "delete" || $mode == "get") {
        return true;
    }
    foreach (CONSTRAINS as $param => $pattern) {
        $idCheckSkip = $mode == "add" && $param == "id";
        if (!$idCheckSkip && (empty($params[$param]) || preg_match($pattern, $params[$param]))) {
            return false;
        }
    }
    return true;
}

function changeItemsTree() {

}

function changeCurrentIdsArray() {

}

function rebuildIdsArray() {

}

function deleteItem($itemParams) {
    $item = \DB\query("SELECT * FROM Items WHERE id = :id", \DB\SELECT_QUERY, array(
        "id" => $itemParams["id"]
    ));
    if (count($item) == 0) {
        return -2;
    }
    \DB\query("DELETE FROM Items WHERE `id` = :id", \DB\DELETE_QUERY, array(
        "id" => $itemParams["id"]
    ));
    \Memcached\delete("catalog/item/{$itemParams["id"]}");
    return 0;
}

function editItem($itemParams, $id) {
    $itemParams["id"] = $id;
    $item = \DB\query("SELECT * FROM Items WHERE id = :id", \DB\SELECT_QUERY, array(
        "id" => $id
    ));
    if (count($item) == 0) {
        return -2;
    }
    \DB\query("UPDATE Items SET `title` = :title, `description` = :description, `price` = :price, `image` = :image WHERE `id` = :id", \DB\UPDATE_QUERY, $itemParams);
    $cacheItem = array();
    foreach (ITEMS_CACHE_FIELDS as $field) {
        $cacheItem[$field] = $itemParams[$field];
    }
    \Memcached\set("catalog/item/{$id}", $cacheItem);
    return $item;
}

function addItem($itemParams) {
    $newItemID = \DB\query("INSERT INTO Items(`title`, `description`, `price`, `image`) VALUES(:title, :description, :price, :image)", \DB\INSERT_QUERY, $itemParams);
    $fields = implode(",", ITEMS_CACHE_FIELDS);
    $item = \DB\query("SELECT {$fields} FROM Items WHERE id = :id", \DB\SELECT_QUERY, array(
        "id" => $newItemID
    ));
    \Memcached\add("catalog/item/{$newItemID}", $item);
    return $item;
}

function getItem($itemParams) {
    $item = \DB\query("SELECT * FROM Items WHERE id = :id", \DB\SELECT_QUERY, array(
        "id" => $itemParams["id"]
    ));
    if (count($item) == 0) {
        return -2;
    }
    return $item;
}