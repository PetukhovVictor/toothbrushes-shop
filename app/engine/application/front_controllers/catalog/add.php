<?php namespace FrontController;

require_once BACK_CONTROLLERS_DIR . "/catalog/CRUD.php";
require_once SYS_DIR . "/json/output.php";

function addItem($params) {
    if (!\BackController\paramsCheck($params, "add")) {
        return \Bootstrap\output(-1, "Incorrect fields.");
    }
    $item = \BackController\addItem($params);
    return \Bootstrap\output(0, "OK", array(
        "item" => $item
    ));
}

$result = addItem($_POST);

\JSON\output($result);