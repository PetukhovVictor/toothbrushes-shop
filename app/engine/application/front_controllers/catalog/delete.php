<?php namespace FrontController;

require_once BACK_CONTROLLERS_DIR . "/catalog/CRUD.php";
require_once SYS_DIR . "/json/output.php";

function deleteItem($params) {
    if (!\BackController\paramsCheck($params, "delete")) {
        \JSON\output(-1, "Incorrect params.");
        return -1;
    }
    $resultEdit = \BackController\deleteItem($params);
    if ($resultEdit == -2) {
        \JSON\output(-2, "Item with ID not exist.");
        return -2;
    }
    return 0;
}

deleteItem($_POST);

\JSON\output(0, "OK");