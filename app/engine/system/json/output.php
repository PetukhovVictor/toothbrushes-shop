<?php namespace JSON;

function output($output) {
    header("Content-type: application/json");
    echo json_encode($output);
    die;
}