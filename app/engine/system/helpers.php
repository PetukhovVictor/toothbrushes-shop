<?php namespace Helpers;

function directoriesCalcPath($id, $filesLimit = 100, $root = true) {
    $dividing = floor($id / $filesLimit);
    $modulo = $id % $filesLimit;
    return ($dividing > $filesLimit ? directoriesCalcPath($dividing, $filesLimit, false) . "/" . $dividing % $filesLimit : $dividing) . ($root ? "/" . $modulo : "");
}

function directoriesCreatePath($directory, $path) {
    $paths = explode("/", $path);
    unset($paths[count($paths) - 1]);
    $nesting = $directory;
    foreach ($paths as $dir) {
        $nesting .= "/" . $dir;
        if (!is_dir($nesting)) {
            mkdir($nesting);
        }
    }
    return $directory . "/" . $path;
}