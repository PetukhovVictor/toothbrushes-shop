<?php namespace FrontController;

require_once SYS_DIR . "/templates/compiler.php";
require_once SYS_DIR . "/templates/output.php";
require_once SYS_DIR . "/json/output.php";
require_once BACK_CONTROLLERS_DIR . "/catalog/list.php";

const ITEMS_ONE_PAGE = 40;
const FORMATS = array("html", "json");
const ORDERS = array("id:desc", "price:asc", "price:desc");

function loadItems($params, $order) {
    $order = explode(":", $order);
    if (!\BackController\paramsCheck($params)) {
        return \Bootstrap\output(-1, "Params is incorrect.");
    }
    $page = empty($params["page"]) ? 1 : $params["page"];
    $items = \BackController\loadItems($page, $order[0], $order[1], ITEMS_ONE_PAGE);
    return \Bootstrap\output(0, "OK", array(
        "items" => $items
    ));
}

$order = !empty($_COOKIE["order"]) && in_array($_COOKIE["order"], ORDERS) ? $_COOKIE["order"] : ORDERS[0];
$result = loadItems($_GET, $order);

$format = !empty($_GET["format"]) && in_array($_GET["format"], FORMATS) ? $_GET["format"] : FORMATS[0];
switch ($format) {
    case "html":
        $params = null;
        if (!empty($_GET["wrap"]) && $_GET["wrap"] == "without") {
            $params = array(
                "actionTemplate" => "/listWithoutWrap"
            );
        }
        $content = \Bootstrap\loadTemplate($result, $params);
        \Template\output($content);
        break;
    case "json":
        \JSON\output($result);
        break;
}

