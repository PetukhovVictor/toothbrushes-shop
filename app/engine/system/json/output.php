<?php namespace JSON;

function output($status_code, $message, $data = null) {
    $output = array(
        "status_code"   => $status_code,
        "message" => $message
    );
    if ($data != null) {
        $output["data"] = $data;
    }
    header("Content-type: application/json");
    echo json_encode($output);
    die;
}