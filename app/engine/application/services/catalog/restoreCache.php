<?php namespace Service;

define("HOME_DIR", dirname(__FILE__) . "/../../../..");
define("SYS_DIR", HOME_DIR . "/engine/system");
define("STORAGE_DIR", SYS_DIR . "/storage");

require_once SYS_DIR . "/config.php";
require_once STORAGE_DIR . "/database/mysql.php";
require_once STORAGE_DIR . "/cache/memcached.php";

const CACHE_SNAPSHOTS_DIRECTORY = HOME_DIR . "/../data/cache_snapshots";

function cacheRestoreSection($directory, $section) {
    $idsDir = CACHE_SNAPSHOTS_DIRECTORY . $directory . "/" . $section;
    foreach(scandir($idsDir) as $field) {
        if ($field == "." || $field == "..") {
            continue;
        }
        $fieldDir = $idsDir . "/" . $field;
        foreach(scandir($fieldDir) as $item) {
            if ($item == "." || $item == "..") {
                continue;
            }
            $content = file_get_contents($fieldDir . "/" . $item);
            $path = $directory . "/" . $section . "/" . $field . "/" . $item;
            fprintf(STDOUT, "Restored: %s\n", $path);
            \Memcached\add($path, unserialize($content));
        }
    }
}

function cacheRestore() {
    $directory = "/catalog/items";
    cacheRestoreSection($directory, "ids");
    cacheRestoreSection($directory, "sectors");
}

$startTime = microtime(true);
cacheRestore();
fprintf(STDOUT, "------------------------------\n");
fprintf(STDOUT, "Cache restored in %.3f seconds\n", (microtime(true) - $startTime));