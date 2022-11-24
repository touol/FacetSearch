<?php
if (file_exists(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
} else {
    require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.core.php';
}
/** @noinspection PhpIncludeInspection */
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
/** @noinspection PhpIncludeInspection */
require_once MODX_CONNECTORS_PATH . 'index.php';
/** @var FacetSearch $FacetSearch */
$FacetSearch = $modx->getService('FacetSearch', 'FacetSearch', MODX_CORE_PATH . 'components/facetsearch/model/');
$modx->lexicon->load('facetsearch:default');

// handle request
$corePath = $modx->getOption('facetsearch_core_path', null, $modx->getOption('core_path') . 'components/facetsearch/');
$path = $modx->getOption('processorsPath', $FacetSearch->config, $corePath . 'processors/');
$modx->getRequest();

/** @var modConnectorRequest $request */
$request = $modx->request;
$request->handleRequest([
    'processors_path' => $path,
    'location' => '',
]);