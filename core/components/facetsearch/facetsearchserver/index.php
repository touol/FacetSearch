<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
$config = [
    'host'=>'https://localhost:9200',   //Адрес OpenSearch
    'login'=>'admin',                   //Логин
    'password'=>'admin',                //Пароль
    'index'=>'demo.loc',                //Индекс базы(такой же как на вашем сайте в настройках FacetSearch)
    'api_key'=>'',                      //Ключ АПИ. Сгенирируйте с помощью какого-либо генератора паролей. Его же забить в настройки FacetSearch.
];
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/facet.class.php';
$facet = new facet($config);
echo json_encode($facet->run());