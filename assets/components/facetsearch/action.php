<?php
if (empty($_REQUEST['action']) and empty($_REQUEST['facetsearch_action'])) {
    $message = 'Access denied action.php';
    echo json_encode(
            ['success' => false,
            'message' => $message,]
            );
    return;
}

define('MODX_API_MODE', true);
require dirname(dirname(dirname(dirname(__FILE__)))) . '/index.php';

$_REQUEST['action'] = $_REQUEST['action'] ? $_REQUEST['action'] : $_REQUEST['facetsearch_action'];

$sp = [];
if($_REQUEST['hash']){
    $sp = $_SESSION['FacetSearch'][$_REQUEST['hash']];
}
if(!$facetsearch = $modx->getService("facetsearch","facetsearch",
    MODX_CORE_PATH."components/facetsearch/model/",$sp)){
    $message =  'Could not create facetsearch!';
    echo json_encode(
        ['success' => false,
        'message' => $message,]
        );
    return;
}

$modx->lexicon->load('facetsearch:default');

$response = $facetsearch->handleRequest($_REQUEST['action'],$_REQUEST);

echo json_encode($response);
exit;