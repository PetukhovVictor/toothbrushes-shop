<?php namespace Config;

const EXT = ".php";

const ROUTERS = array(
    "catalog" => array(
        "path" => FRONT_CONTROLLERS_DIR . "/catalog",
        "template" => TEMPLATES_DIR . "/catalog",
        "actions" => array(
            "add" => array(
                "path" => "/add",
                "template" => "/manage"
            ),
            "delete" => array(
                "path" => "/delete"
            ),
            "edit" => array(
                "path" => "/edit",
                "template" => "/manage"
            ),
            "get" => array(
                "path" => "/get",
                "template" => "/item"
            ),
            "list" => array(
                "path" => "/list",
                "template" => "/list"
            ),
        )
    ),
    "errors" => array(
        "path" => FRONT_CONTROLLERS_DIR . "/errors",
        "actions" => array(
            "404" => array(
                "path" => "/404"
            )
        )
    )
);