<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
/** @var modX $modx */
/** @var array $scriptProperties */
/** @var FacetSearch $FacetSearch */
if(empty($scriptProperties['parents'])) $scriptProperties['parents'] = $modx->resource->id;
if(empty($scriptProperties['limit'])){
    $limit = $scriptProperties['limit'] = 10;
}else{
    $limit = $scriptProperties['limit'];
}
$start_limit = $limit;

$FacetSearch = $modx->getService('FacetSearch', 'FacetSearch', MODX_CORE_PATH . 'components/facetsearch/model/', $scriptProperties);
if (!$FacetSearch) {
    return 'Could not load FacetSearch class!';
}

// Do your snippet code here. This demo grabs 5 items from our custom table.
$tpl = $modx->getOption('tpl', $scriptProperties, 'Item');
$sortby = $modx->getOption('sortby', $scriptProperties, 'name');
$sortdir = $modx->getOption('sortbir', $scriptProperties, 'ASC');
$limit = $modx->getOption('limit', $scriptProperties, 5);
$outputSeparator = $modx->getOption('outputSeparator', $scriptProperties, "\n");
$toPlaceholder = $modx->getOption('toPlaceholder', $scriptProperties, false);
if (empty($toPlaceholders) && !empty($toPlaceholder)) {$toPlaceholders = $toPlaceholder;}


$hash = sha1(serialize($scriptProperties));
$_SESSION['FacetSearch'][$hash] = $scriptProperties;

if (!empty($_REQUEST[$scriptProperties['pageVarKey']])) {
    $page = (int) $_REQUEST[$scriptProperties['pageVarKey']];
}

//sort
$start_sort = implode(',', array_map('trim' , explode(',', $scriptProperties['sort'])));
if (!empty($_REQUEST['sort'])) {$sort = $_REQUEST['sort'];}
elseif (!empty($start_sort)) {$sort = $start_sort;}

$config = [
    'actionUrl' => $modx->getOption('assets_url'). 'components/facetsearch/action.php',
    'cssUrl' => $modx->getOption('assets_url'). 'components/facetsearch/css/web/',
    'jsUrl' => $modx->getOption('assets_url'). 'components/facetsearch/js/web/',
    'mode' => in_array($scriptProperties['ajaxMode'], array('button', 'scroll')) ? $scriptProperties['ajaxMode'] : '',
    'moreText' => 'Загрузить еще',//$modx->lexicon('mse2_more'),
    'pageVar' => $scriptProperties['pageVarKey'],
    'page' => $page,
    'pageId' => $modx->resource->id,

    'start_sort' => $start_sort,
    'sort' => $sort == $start_sort ? '' : $sort,
    'start_limit' => $start_limit,
    'limit' => $limit == $start_limit ? '' : $limit,
];
if (!empty($scriptProperties['filterOptions'])) {
	$filterOptions = $modx->fromJSON($scriptProperties['filterOptions']);
	if (is_array($filterOptions)) {
		$config['filterOptions'] = $filterOptions;
	}
}
$config_js = preg_replace(array('/^\n/', '/\t{5}/'), '', '
                            FacetSearch = {};
                            FacetSearchConfig = ' . $modx->toJSON($config) . ';
                    ');
//$modx->regClientCSS('/assets/components/barcode/css/web/default.css');
$path = $modx->getOption('assets_url').'components/facetsearch/';
if($js) $modx->regClientStartupScript("<script type=\"text/javascript\">\n" . $config_js . "\n</script>", true);    
if($js) $modx->regClientScript($path.$js);
if($css) $modx->regClientCSS($path.$css);
$FacetSearch->addTime('FacetSearch start');
$response = $FacetSearch->handleRequest('filter',$_REQUEST);
//$results = $response['data']['results'];
$filters = implode("\r\n",$response['data']['filters']);
$sorts = [];
if($sort){
    $sorts0 = explode(',',$sort);
    foreach($sorts0 as $sort){
        $sorts[$sort] = 1;
    }
}
$output = [
    'hash'=>$hash,
    'filters'=>$filters,
    'results'=>$response['data']['results'],
    'total'=>$response['data']['total'],
    'pagination'=>$response['data']['pagination'],
    'sorts'=>$sorts,
    'limit'=>$limit,
];
if($response['data']['log']) $output['log'] = $response['data']['log'];
if (!empty($toSeparatePlaceholders)) {
	$modx->setPlaceholders($filters, $toSeparatePlaceholders);
	$modx->setPlaceholders($output, $toSeparatePlaceholders);
}
else {
	if (!empty($toPlaceholders)) {
		$output['log'] = $log;
		$modx->setPlaceholders($output, $toPlaceholders);
	}else {
		return $FacetSearch->pdo->getChunk($scriptProperties['tplOuter'], $output);
	}
}
return;
