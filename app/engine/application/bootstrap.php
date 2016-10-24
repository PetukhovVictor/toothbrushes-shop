<?php namespace Bootstrap;

require_once APP_DIR . "/config.php";

function loadFrontController($module, $action) {
    if (empty(\Config\ROUTERS[$module])) {
        throw new \Exception("404");
    }
    $moduleParams = \Config\ROUTERS[$module];
    if (empty($moduleParams["actions"][$action])) {
        throw new \Exception("404");
    }
    $actionParams = $moduleParams["actions"][$action];
    return $moduleParams["path"] . $actionParams["path"] . \Config\EXT;
}

$module = $_GET["module"];
$action = $_GET["action"];

try {
    $frontController = loadFrontController($module, $action);
} catch (\Exception $e) {
    $error = $e->getMessage();
    $frontController = loadFrontController("errors", $error);
}

if (!file_exists($frontController)) {
    throw new \Exception("Front controller not found.");
}

define("MODULE_NAME", $module);
define("ACTION_NAME", $action);

function loadTemplate($environment, $params = null) {
    $moduleParams = \Config\ROUTERS[MODULE_NAME];
    $actionParams = $moduleParams["actions"][ACTION_NAME];
    $moduleTemplate = $params != null && $params["moduleTemplate"] ? $params["moduleTemplate"] : $moduleParams["template"];
    $actionTemplate = $params != null && $params["actionTemplate"] ? $params["actionTemplate"] : $actionParams["template"];
    $template = \Template\load($moduleTemplate . $actionTemplate);
    return \Template\compile($template, $environment);
}

function output($status_code, $message = null, $data = null) {
    $output = array(
        "status_code" => $status_code
    );
    if ($message != null) {
        $output["message"] = $message;
    }
    if ($data != null) {
        $output["data"] = $data;
    }
    return $output;
}

require_once $frontController;