<?php
/** @var modX $modx */
/* @var array $scriptProperties */
/* @var FacetSearch $FacetSearch*/
$FacetSearch = $modx->getService('facetsearch', 'FacetSearch', $modx->getOption('facetsearch_core_path', $scriptProperties, $modx->getOption('core_path') . 'components/facetsearch/') . 'model/');
if (!$FacetSearch){
    $modx->log(1,"[FacetSearch] not run plugin!");
    return;
}

switch ($modx->event->name) {
    
    case 'OnDocFormSave':
        if($resource){
            if(!$fsPubDelRes = $modx->getObject('fsPubDelRes',['resource_id'=>(int)$id])){
                $fsPubDelRes = $modx->newObject('fsPubDelRes',['resource_id'=>(int)$id]);
            }
            if($fsPubDelRes){
                if($resource->get('published')){
                    $fsPubDelRes->status_id = 2;
                }else{
                    $fsPubDelRes->status_id = 1;
                }
                $fsPubDelRes->active = true;
                $fsPubDelRes->save();
            }
        }    
    break;
    case 'OnDocFormDelete':
        $ids = $children;
        $ids[] = $id;
        foreach($ids as $rid){
            if(!$fsPubDelRes = $modx->getObject('fsPubDelRes',['resource_id'=>(int)$rid])){
                $fsPubDelRes = $modx->newObject('fsPubDelRes',['resource_id'=>(int)$rid]);
            }
            if($fsPubDelRes){
                $fsPubDelRes->status_id = 1;
                $fsPubDelRes->active = true;
                $fsPubDelRes->save();
            }
        }    
    break;
    case 'OnResourceUndelete':
        if(!$fsPubDelRes = $modx->getObject('fsPubDelRes',['resource_id'=>(int)$id])){
            $fsPubDelRes = $modx->newObject('fsPubDelRes',['resource_id'=>(int)$id]);
        }
        if($fsPubDelRes){
            $fsPubDelRes->status_id = 2;
            $fsPubDelRes->active = true;
            $fsPubDelRes->save();
            $FacetSearch->unDeleteChildren($id);
        }
    break;
    case 'OnDocUnPublished':
        if(!$fsPubDelRes = $modx->getObject('fsPubDelRes',['resource_id'=>(int)$id])){
            $fsPubDelRes = $modx->newObject('fsPubDelRes',['resource_id'=>(int)$id]);
        }
        if($fsPubDelRes){
            $fsPubDelRes->status_id = 1;
            $fsPubDelRes->active = true;
            $fsPubDelRes->save();
        }
    break;
    case 'OnDocPublished':
        if(!$fsPubDelRes = $modx->getObject('fsPubDelRes',['resource_id'=>(int)$id])){
            $fsPubDelRes = $modx->newObject('fsPubDelRes',['resource_id'=>(int)$id]);
        }
        if($fsPubDelRes){
            $fsPubDelRes->status_id = 2;
            $fsPubDelRes->active = true;
            $fsPubDelRes->save();
        }
    break;
}
return;