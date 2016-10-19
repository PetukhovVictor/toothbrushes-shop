<?php namespace FrontController;

require_once BACK_CONTROLLERS_DIR . "/catalog/CRUD.php";
require_once SYS_DIR . "/json/output.php";

function editItem($params) {
    if (!\BackController\paramsCheck($params, "edit")) {
        return \Bootstrap\output(-1, "Incorrect fields.");
    }
    $resultEdit = \BackController\editItem($params, $params["id"]);
    if ($resultEdit == -2) {
        return \Bootstrap\output(-2, "Item with ID not exist.");
    }
    return \Bootstrap\output(0, "OK", array(
        "item" => $resultEdit
    ));
}

$result = editItem($_POST);

\JSON\output($result);