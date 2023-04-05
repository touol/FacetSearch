<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
$config = [
    'host'=>'https://localhost:9200',
    'login'=>'admin',
    'password'=>'password',
    'index'=>'demo.loc',
    'api_key'=>'',
];
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/facet.class.php';
$facet = new facet($config);
echo json_encode($facet->run());