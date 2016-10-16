<?php namespace BackController;

require_once SYS_DIR . "/config.php";
require_once STORAGE_DIR . "/database/mysql.php";

function loadItems() {
    $items = \DB\query("SELECT * FROM Items LIMIT 0,25", \DB\SELECT_QUERY);
    return $items;
}