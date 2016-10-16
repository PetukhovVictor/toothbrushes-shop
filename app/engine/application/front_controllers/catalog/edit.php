<?php namespace FrontController;

require_once BACK_CONTROLLERS_DIR . "/catalog/CRUD.php";
require_once SYS_DIR . "/json/output.php";

function editItem($params) {
    if (!\BackController\paramsCheck($params, "edit")) {
        \JSON\output(-1, "Incorrect fields.");
        return false;
    }
    $resultEdit = \BackController\editItem($params, $params["id"]);
    if ($resultEdit == -2) {
        \JSON\output(-2, "Item with ID not exist.");
    }
    return $resultEdit;
}

$items = editItem($_POST);

\JSON\output(0, "OK", $items);