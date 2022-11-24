<?php
/** @var modX $modx */
/* @var array $scriptProperties */
switch ($modx->event->name) {
    case 'OnDocFormDelete':
        /* @var FacetSearch $FacetSearch*/
        $FacetSearch = $modx->getService('facetsearch', 'FacetSearch', $modx->getOption('facetsearch_core_path', $scriptProperties, $modx->getOption('core_path') . 'components/facetsearch/') . 'model/');
        if ($FacetSearch instanceof FacetSearch) {
            //$modx->log(1,"OnDocFormDelete " .print_r($id,1).print_r($children,1));
            // $table = $modx->getTableName('fsDeletedResource');
            // $sql = "INSERT INTO $table (`resource_id`) VALUES ($id);";
            // if(!empty($children)){
            //     foreach($children as $rid){
            //         $sql .= " INSERT INTO $table (`resource_id`) VALUES ($rid);";
            //     }
            // }
            // $modx->query($sql);
        }
        break;
}
return '';