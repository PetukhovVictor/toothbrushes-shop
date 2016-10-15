<?php namespace FrontController;

require_once TEMPLATES_DIR . "/compiler.php";
require_once BACK_CONTROLLERS_DIR . "/catalog/list.php";

function loadItems() {
    $items = \BackController\loadItems();
    return $items;
}

$items = loadItems();
$content = \Bootstrap\loadTemplate(array(
    "items" => $items
));

echo $content;