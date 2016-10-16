<?php namespace FrontController;

require_once SYS_DIR . "/templates/compiler.php";
require_once SYS_DIR . "/templates/output.php";
require_once BACK_CONTROLLERS_DIR . "/catalog/list.php";

function loadItems() {
    $items = \BackController\loadItems();
    return $items;
}

$items = loadItems();

$content = \Bootstrap\loadTemplate(array(
    "items" => $items
));

\Template\output($content);