<?php

use NorsysBank\utils\RegisterControllers;
use NorsysBank\utils\Router;

ini_set('display_errors', 1);
error_reporting(E_ERROR|E_CORE_ERROR|E_PARSE);

if(isset($_GET['sid'])) {
    $set = session_id($_GET['sid']); //call before session_start()
}
session_start();

require __DIR__ . '/../vendor/autoload.php';

function flatten(array $array) {
    $return = array();
    array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
    return $return;
}

function cors() {
    header("Access-Control-Allow-Origin: *");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
    header("Access-Control-Allow-Methods: GET, PUT, POST, DELETE, PATCH, OPTION");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit(0);
    }
}

define('__ROOT__', realpath(__DIR__.'/../'));

cors();

RegisterControllers::instantiate(__DIR__.'/../src/controllers')->register();

echo Router::instantiate()->match($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], $_SERVER['QUERY_STRING']);