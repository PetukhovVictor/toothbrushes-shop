<?php namespace FrontController;

require_once BACK_CONTROLLERS_DIR . "/catalog/CRUD.php";
require_once SYS_DIR . "/json/output.php";

function getItem($params) {
    if (!\BackController\paramsCheck($params, "get")) {
        return \Bootstrap\output(-1, "Incorrect ID.");
    }
    $item = \BackController\getItem($params["id"]);
    if ($item == -2) {
        return \Bootstrap\output(-2, "Item with ID not exist.");
    }
    return \Bootstrap\output(0, "OK", array(
        "item" => $item
    ));
}

$result = getItem($_GET);

\JSON\output($result);