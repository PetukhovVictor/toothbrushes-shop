<?php namespace DB;

const SELECT_QUERY = 1;
const INSERT_QUERY = 2;
const UPDATE_QUERY = 3;
const DELETE_QUERY = 4;

$mysqli = null;

function connect() {
    $GLOBALS["mysqli"] = mysqli_connect(\Config\DB["host"], \Config\DB["user"], \Config\DB["password"], \Config\DB["name"]);
    if (mysqli_connect_errno()) {
        throw new \Exception("Error connect to DB");
    }
    mysqli_set_charset($GLOBALS["mysqli"], "utf8");
}

function queryBuild($query, $params) {
    $pattern = array();
    $replacement = array();
    foreach($params as $param => $value) {
        $pattern[] = ":" . $param;
        $replacement[] = '"' . mysqli_real_escape_string($value) . '"';
    }
    return preg_replace($pattern, $replacement, $query);
}

function query($query, $type, $params = null) {
    if ($GLOBALS["mysqli"] == null) {
        connect();
    }
    $query = $params != null ? queryBuild($query, $params) : $query;
    $result = mysqli_query($GLOBALS["mysqli"], $query);
    switch ($type) {
        case SELECT_QUERY:
            $data = array();
            while ($row = mysqli_fetch_assoc($result)) {
                if ($row != null) {
                    $data[] = $row;
                }
            }
            return $data;
            break;
        case INSERT_QUERY:
            return mysqli_insert_id($GLOBALS["mysqli"]);
            break;
    }
}