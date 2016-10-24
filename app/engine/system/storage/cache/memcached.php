<?php namespace Memcached;

$memcached = null;

function connect() {
    $GLOBALS["memcached"] = new \Memcached();
    $GLOBALS["memcached"]->addServer(\Config\MEMCACHED["host"], \Config\MEMCACHED["port"]);
}

function get($key) {
    if ($GLOBALS["memcached"] == null) {
        connect();
    }
    return $GLOBALS["memcached"]->get($key);
}

function add($key, $value) {
    if ($GLOBALS["memcached"] == null) {
        connect();
    }
    return $GLOBALS["memcached"]->add($key, $value);
}

function set($key, $value) {
    if ($GLOBALS["memcached"] == null) {
        connect();
    }
    return $GLOBALS["memcached"]->set($key, $value);
}

function delete($key) {
    if ($GLOBALS["memcached"] == null) {
        connect();
    }
    return $GLOBALS["memcached"]->delete($key);
}

function increment($key) {
    if ($GLOBALS["memcached"] == null) {
        connect();
    }
    return $GLOBALS["memcached"]->increment($key);
}

function decrement($key) {
    if ($GLOBALS["memcached"] == null) {
        connect();
    }
    return $GLOBALS["memcached"]->decrement($key);
}

function stats() {
    if ($GLOBALS["memcached"] == null) {
        connect();
    }
    $stats = $GLOBALS["memcached"]->getStats();
    return $stats[\Config\MEMCACHED["host"] . ":" . \Config\MEMCACHED["port"]];
}