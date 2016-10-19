<?php namespace FrontController;

require_once BACK_CONTROLLERS_DIR . "/catalog/CRUD.php";
require_once SYS_DIR . "/json/output.php";

function deleteItem($params) {
    if (!\BackController\paramsCheck($params, "delete")) {
        return \Bootstrap\output(-1, "Incorrect ID");
    }
    $resultEdit = \BackController\deleteItem($params);
    if ($resultEdit == -2) {
        return \Bootstrap\output(-2, "Item with ID not exist.");
    }
    return \Bootstrap\output(0, "OK");
}

$result = deleteItem($_GET);

\JSON\output($result);