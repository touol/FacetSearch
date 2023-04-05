<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/facet.class.php';
$facet = new facet();
echo json_encode($facet->run());