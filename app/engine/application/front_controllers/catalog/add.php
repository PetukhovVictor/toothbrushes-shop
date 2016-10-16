<?php namespace FrontController;

require_once BACK_CONTROLLERS_DIR . "/catalog/CRUD.php";
require_once SYS_DIR . "/json/output.php";

const PARAMS = array(
    "title" => '^.{1,255}$',
    "description" => '^.{1,65535}$',
    "price" => '^[+-]([0-9]*[.])?[0-9]+$',
    "image" => '^http:\/\/[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+$'
);

function paramsCheck($params) {
    foreach (PARAMS as $param => $pattern) {
        if (empty($params[$param]) || preg_match($pattern, $params[$param])) {
            return false;
        }
    }
    return true;
}

function addItem($params) {
    if (!paramsCheck($params)) {
        \JSON\output(1, "Incorrect fields.");
        return false;
    }
    $items = \BackController\addItem($params);
    return $items;
}

$items = addItem($_POST);

\JSON\output(0, "OK", $items);