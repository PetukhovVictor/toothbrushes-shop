<?php namespace BackController;

require_once SYS_DIR . "/config.php";
require_once STORAGE_DIR . "/database/mysql.php";
require_once STORAGE_DIR . "/cache/memcached.php";
require_once SYS_DIR . "/data_structures/RBTree.php";
require_once SYS_DIR . "/helpers.php";

const CONSTRAINS = array(
    "id" => '^[1-9]\d*?$',
    "title" => '^.{1,255}$',
    "description" => '^.{1,65535}$',
    "price" => '^[+-]([0-9]*[.])?[0-9]+$',
    "image" => '^http:\/\/[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-]+$'
);

const ARRAYS_ID_MAX_DEFLECTION_FACTOR = 0.9; // d factor = (actual size - predefined size) / actual size
const CACHE_SNAPSHOTS_DIRECTORY_FILES_LIMIT = 100;
const ITEMS_CACHE_FIELDS = array("title", "price", "image");
const ORDERING_FIELDS = array("id", "price");
const ITEMS_CACHE_FIELD_KEY = "id";
const CACHE_SNAPSHOTS_DIRECTORY = HOME_DIR . "/../../data/cache_snapshots";

function cacheSnapshot($path, $key, $value) {
    $dir = createCacheSnapshotDirectories($path);
    file_put_contents(($key == null ? $path : $dir . "/" . $key) . ".data", serialize($value));
}

function createCacheSnapshotDirectories($path) {
    $path = explode("/", $path);
    $currentDir = CACHE_SNAPSHOTS_DIRECTORY;
    foreach($path as $dir) {
        $currentDir = $currentDir . "/" . $dir;
        if (!is_dir($currentDir)) {
            mkdir($currentDir);
        }
    }
    return $currentDir;
}

function paramsCheck($params, $mode = "add") {
    if (empty($params) || !is_array($params)) {
        return false;
    }
    if ($mode == "delete" || $mode == "get") {
        return true;
    }
    foreach (CONSTRAINS as $param => $pattern) {
        $idCheckSkip = $mode == "add" && $param == "id";
        if (!$idCheckSkip && (empty($params[$param]) || preg_match($pattern, $params[$param]))) {
            return false;
        }
    }
    return true;
}

function changeItemsTree() {

}

function changeCurrentIdsArray() {

}

function rebuildIdsArray() {

}

function sectorInsertItem($field, $target, $id) {
    $sectorNumber = $target["sector"];
    $path = "catalog/items/ids/by_{$field}";
    $idsFile = file_get_contents(CACHE_SNAPSHOTS_DIRECTORY . "/{$path}/{$sectorNumber}.data");
    $index = null;
    if ($idsFile !== false) {
        $idsFile = unserialize($idsFile);
        if ($target["index"] == -1) {
            $idsFile = array_merge(array($id), $idsFile);
        } else {
            $leftArray = array_slice($idsFile, 0, $target["index"]);
            $idsFile = array_merge($leftArray, array($id), array_slice($idsFile, $target["index"] + 1));
        }
        file_put_contents(CACHE_SNAPSHOTS_DIRECTORY . "/{$path}/{$sectorNumber}.data", serialize($idsFile));
    }
    $idsCache = \Memcached\get("{$path}/{$sectorNumber}");
    if ($idsCache !== false) {
        if ($idsFile === false) {
            $idsCache = array_merge(array_slice($idsCache, 0, $target["index"]), array($id), array_slice($idsCache, $target["index"] + 1));
        } else {
            $idsCache = $idsFile;
        }
        \Memcached\set("{$path}/{$sectorNumber}", $idsCache);
    }
    $path = \Helpers\directoriesCalcPath($id, CACHE_SNAPSHOTS_DIRECTORY_FILES_LIMIT);
    $dir = createCacheSnapshotDirectories("catalog/items/entries");
    $dir = \Helpers\directoriesCreatePath($dir, $path);
    if (is_file($dir . ".data")) {
        $sectors = file_get_contents($dir . ".data");
        $sectors = unserialize($sectors);
        $currentSectors = array(
            "by_" . $field => array(
                "number" => $target["sector"],
                "index" => $target["index"]
            )
        );
        $sectors = array_merge($currentSectors, $sectors);
    } else {
        $sectors = array(
            "by_" . $field => array(
                "number" => $target["sector"],
                "index" => $target["index"]
            )
        );
    }
    cacheSnapshot($dir, null, $sectors);
}

function getSectorsArray($field) {
    $path = "catalog/items/sectors/by_{$field}";
    $sectorsArray = \Memcached\get("{$path}/array");
    if ($sectorsArray === false) {
        $sectorsArray = file_get_contents(CACHE_SNAPSHOTS_DIRECTORY . "/{$path}/array.data");
        $sectorsArray = unserialize($sectorsArray);
        \Memcached\add("{$path}/array", $sectorsArray);
    }
    return $sectorsArray;
}

function getTargetSector($field, $item, $id) {
    $path = "catalog/item";
    $where = $field != ITEMS_CACHE_FIELD_KEY
        ? "(id < {$id} AND {$field} = {$item[$field]}) OR {$field} < {$item[$field]}"
        : "id < {$id}";
    $orderBy = $field != ITEMS_CACHE_FIELD_KEY
        ? "{$field} DESC, " . ITEMS_CACHE_FIELD_KEY . " DESC"
        : ITEMS_CACHE_FIELD_KEY . " DESC";
    $resultSet = \DB\query("SELECT * FROM Items WHERE {$where} ORDER BY {$orderBy} LIMIT 1", \DB\SELECT_QUERY);
    if (count($resultSet) == 0) {
        return array(
            "sector" => 1,
            "index" => -1
        );
    }
    $prevId = $resultSet[0]["id"];
    $prevItem = \Memcached\get("{$path}/{$prevId}");
    if ($prevItem === false) {
        $path = \Helpers\directoriesCalcPath($prevId, CACHE_SNAPSHOTS_DIRECTORY_FILES_LIMIT);
        $sectors = file_get_contents(CACHE_SNAPSHOTS_DIRECTORY . "/catalog/items/entries/" . $path . ".data");
        $sectors = unserialize($sectors);
        $prevItem = array(
            "sectors" => $sectors
        );
    }
    return array(
        "sector" => $prevItem["sectors"]["by_" . $field]["number"],
        "index" => $prevItem["sectors"]["by_" . $field]["index"]
    );
}

function sectorsRebuildArray($field, $targetSector, $type) {
    $path = "catalog/items/sectors/by_{$field}";
    $sectorsArrayFile = file_get_contents(CACHE_SNAPSHOTS_DIRECTORY . "/{$path}/array.data");
    $sectorLeftBound = 0;
    $sectorsArray = null;
    if ($sectorsArrayFile !== false) {
        $sectorsArray = unserialize($sectorsArrayFile);
        $sectorLeftBound = $sectorsArray[$targetSector - 1];
        for ($i = $targetSector; $i < count($sectorsArray); $i++) {
            $sectorsArray[$i] += ($type == "increment" ? 1 : -1);
        }
        file_put_contents(CACHE_SNAPSHOTS_DIRECTORY . "/{$path}/array.data", serialize($sectorsArray));
        \Memcached\add("{$path}/array", $sectorsArray);
    }
    $sectorsArrayCache = \Memcached\get("{$path}/array");
    if ($sectorsArrayCache !== false) {
        if ($sectorsArray == null) {
            $sectorsArray = $sectorsArrayCache;
            $sectorLeftBound = $sectorsArray[$targetSector - 1];
            for ($i = $targetSector; $i < count($sectorsArray); $i++) {
                $sectorsArray[$i] += ($type == "increment" ? 1 : -1);
            }
        }
        \Memcached\set("{$path}/array", $sectorsArray);
    }
    return $sectorLeftBound;
}

function sectorsRebuildBoundsTree($field, $targetSector, $type) {
    $path = "catalog/items/sectors/by_{$field}";
    $sectorsBoundsTreeFile = file_get_contents(CACHE_SNAPSHOTS_DIRECTORY . "/{$path}/bounds_tree.data");
    $sectorsBoundsTree = null;
    if ($sectorsBoundsTreeFile !== false) {
        $sectorsBoundsTree = unserialize($sectorsBoundsTreeFile);
        $targetNode = $sectorsBoundsTree->findKey($sectorsBoundsTree, $targetSector["bound"]);
        $nextNode = $targetNode;
        while ($nextNode = $sectorsBoundsTree->treeSuccessor($sectorsBoundsTree, $nextNode)) {
            $nextNode->key += ($type == "increment" ? 1 : -1);
        }
        file_put_contents(CACHE_SNAPSHOTS_DIRECTORY . "/{$path}/bounds_tree.data", serialize($sectorsBoundsTree));
        \Memcached\add("{$path}/bounds_tree", $sectorsBoundsTree);
    }
    $sectorsBoundsTreeCache = \Memcached\get("{$path}/bounds_tree");
    if ($sectorsBoundsTreeCache !== false) {
        if ($sectorsBoundsTree == null) {
            $sectorsBoundsTree = $sectorsBoundsTreeCache;
            $targetNode = $sectorsBoundsTree->findKey($sectorsBoundsTree, $targetSector["bound"]);
            $nextNode = $targetNode;
            while ($nextNode = $sectorsBoundsTree->treeSuccessor($sectorsBoundsTree, $nextNode)) {
                $nextNode->key += ($type == "increment" ? 1 : -1);
            }
        }
        \Memcached\set("{$path}/bounds_tree", $sectorsBoundsTree);
    }
}

function sectorsChangeBounds($field, $targetSector, $type) {
    $sectorLeftBound = sectorsRebuildArray($field, $targetSector, $type);
    $sector = array(
        "number" => $targetSector,
        "bound" => $sectorLeftBound
    );
    sectorsRebuildBoundsTree($field, $sector, $type);
}

function addItemProcedure($item, $id) {
    $sectors = array();
    foreach (ORDERING_FIELDS as $field) {
        $target = getTargetSector($field, $item, $id);
        sectorInsertItem($field, $target, $id);
        sectorsChangeBounds($field, $target["sector"], "increment");
        $sectors["by_" . $field] = array(
            "number" => $target["sector"],
            "index" => $target["index"] + 1
        );
    }
    $item["sectors"] = $sectors;
    \Memcached\add("catalog/item/{$id}", $item);
    $catalogInfo = \Memcached\get("catalog/items/common/info");
    if ($catalogInfo !== false) {
        $catalogInfo["numberAllItem"] += 1;
        \Memcached\set("catalog/items/common/info", $catalogInfo);
    }
    $catalogInfo = file_get_contents(CACHE_SNAPSHOTS_DIRECTORY . "/catalog/items/common/info.data");
    if ($catalogInfo !== false) {
        $catalogInfo = unserialize($catalogInfo);
        $catalogInfo["numberAllItem"] += 1;
        file_put_contents(CACHE_SNAPSHOTS_DIRECTORY . "/catalog/items/common/info.data", serialize($catalogInfo));
    }
}

function deleteItemProcedure($item, $id) {
    $sectors = array();
    foreach (ORDERING_FIELDS as $field) {
        $target = getTargetSector($field, $item, $id);
        sectorInsertItem($field, $target, $id);
        sectorsChangeBounds($field, $target["sector"], "increment");
        $sectors["by_" . $field] = array(
            "number" => $target["sector"],
            "index" => $target["index"] + 1
        );
    }
    $item["sectors"] = $sectors;
    \Memcached\add("catalog/item/{$id}", $item);
    $catalogInfo = \Memcached\get("catalog/items/common/info");
    if ($catalogInfo !== false) {
        $catalogInfo["numberAllItem"] += 1;
        \Memcached\set("catalog/items/common/info", $catalogInfo);
    }
    $catalogInfo = file_get_contents(CACHE_SNAPSHOTS_DIRECTORY . "/catalog/items/common/info.data");
    if ($catalogInfo !== false) {
        $catalogInfo = unserialize($catalogInfo);
        $catalogInfo["numberAllItem"] += 1;
        file_put_contents(CACHE_SNAPSHOTS_DIRECTORY . "/catalog/items/common/info.data", serialize($catalogInfo));
    }
}

function addItem($itemParams) {
    $newItemID = \DB\query("INSERT INTO Items(`title`, `description`, `price`, `image`) VALUES(:title, :description, :price, :image)", \DB\INSERT_QUERY, $itemParams);
    $fields = implode(",", ITEMS_CACHE_FIELDS);
    $item = \DB\query("SELECT {$fields} FROM Items WHERE id = :id", \DB\SELECT_QUERY, array(
        "id" => $newItemID
    ));
    addItemProcedure($item[0], $newItemID);
    $item[0]["id"] = $newItemID;
    return $item;
}

function deleteItem($itemParams) {
    $item = \DB\query("SELECT * FROM Items WHERE id = :id", \DB\SELECT_QUERY, array(
        "id" => $itemParams["id"]
    ));
    if (count($item) == 0) {
        return -2;
    }
    \DB\query("DELETE FROM Items WHERE `id` = :id", \DB\DELETE_QUERY, array(
        "id" => $itemParams["id"]
    ));
    \Memcached\delete("catalog/item/{$itemParams["id"]}");
    deleteItemProcedure($item[0], $itemParams["id"]);
    return 0;
}

function editItem($itemParams, $id) {
    $itemParams["id"] = $id;
    $item = \DB\query("SELECT * FROM Items WHERE id = :id", \DB\SELECT_QUERY, array(
        "id" => $id
    ));
    if (count($item) == 0) {
        return -2;
    }
    \DB\query("UPDATE Items SET `title` = :title, `description` = :description, `price` = :price, `image` = :image WHERE `id` = :id", \DB\UPDATE_QUERY, $itemParams);
    $cacheItem = array();
    foreach (ITEMS_CACHE_FIELDS as $field) {
        $cacheItem[$field] = $itemParams[$field];
    }
    \Memcached\set("catalog/item/{$id}", $cacheItem);
    return $item;
}

function getItem($itemParams) {
    $item = \DB\query("SELECT * FROM Items WHERE id = :id", \DB\SELECT_QUERY, array(
        "id" => $itemParams
    ));
    if (count($item) == 0) {
        return -2;
    }
    return $item;
}