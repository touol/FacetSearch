<?php

require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

if (!$facetsearch = $modx->getService('facetsearch', 'facetsearch', 
        $modx->getOption('core_path') . 'components/facetsearch/model/', [])
) {
    echo 'Could not load facetsearch class!';
}
echo 'start';
$resp = $facetsearch->upload_resources();
echo json_encode($resp);
echo 'end';