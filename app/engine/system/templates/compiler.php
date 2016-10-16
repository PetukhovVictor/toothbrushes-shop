<?php namespace Template;

const MODE = "native"; // native PHP or simple tpl language
const EXT = MODE == "native" ? \Config\EXT : ".tpl";

const TOKENS = array(
    '({\$([A-z][A-z0-9]*?)})' => '<?=$$1?>',
    '({([^\$].*?)})' => '<?php $1 ?>'
);

function load($templateAddress) {
    $template = file_get_contents($templateAddress . EXT);
    return $template;
}

function execute($templateNative, $environment) {
    extract($environment);
    unset($environment);
    ob_start();
    eval(" ?>" . $templateNative . "<?php ");
    $templateCompiled = ob_get_clean();
    return $templateCompiled;
}

function compile($template, $environment) {
    $templateNative = MODE == "native" ? $template : preg_replace(array_keys(TOKENS), array_values(TOKENS), $template);
    $templateCompiled = execute($templateNative, $environment);
    return $templateCompiled;
}