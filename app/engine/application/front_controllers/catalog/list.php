<?php namespace FrontController;

require_once SYS_DIR . "/templates/compiler.php";
require_once SYS_DIR . "/templates/output.php";
require_once BACK_CONTROLLERS_DIR . "/catalog/list.php";

const ITEMS_ONE_PAGE = 25;

function loadItems($params) {
    if (!\BackController\paramsCheck($params)) {
        return \Bootstrap\output(-1, "Params is incorrect.");
    }
    $page = empty($params["page"]) ? 1 : $params["page"];
    $items = \BackController\loadItems($page, "id", ITEMS_ONE_PAGE);
    return \Bootstrap\output(0, "OK", array(
        "items" => $items
    ));
}

$result = loadItems($_GET);

$content = \Bootstrap\loadTemplate($result);

\Template\output($content);