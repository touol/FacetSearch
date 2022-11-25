<?php

class FacetSearchHandler
{
    /** @var modX $modx */
    public $modx;
    public $pdo;
    public $fs;

    /** @var array() $config */
    public $config = array();
    
    public function __construct(FacetSearch &$fs, array $config = array()) {
        $this->modx =& $fs->modx;
        $this->pdo =& $fs->pdo;
        $this->fs =& $fs;

        $this->config = array_merge(array(
            
        ), $config);
    }

    public function get_options($data){
        //resource
        $fields = $this->modx->getFields('modResource');
        foreach($fields as $field=>$v){
            if($field == "id") continue;
            if(!$opt = $this->modx->getObject("fsOption",['class_id'=>1,'key'=>$field])){
                if($opt = $this->modx->newObject("fsOption",[
                    'class_id'=>1,
                    'key'=>$field,
                    'alias'=>$field,
                    'active'=>0,
                    ])){
                        $opt->save();
                    }
            }
        }
        
        if($this->fs->miniShop2){
            //msProductData
            $fields = $this->modx->getFields('msProductData');
            foreach($fields as $field=>$v){
                if($field == "id") continue;
                if(!$opt = $this->modx->getObject("fsOption",['class_id'=>2,'key'=>$field])){
                    if($opt = $this->modx->newObject("fsOption",[
                        'class_id'=>2,
                        'key'=>$field,
                        'alias'=>$field,
                        'active'=>0,
                        ])){
                            $opt->save();
                        }
                }
            }
            //msOption
            $this->pdo->setConfig([
                'class'=>'msOption',
                'return'=>'data',
                'limit'=>0,
                ]);
            $msOptions = $this->pdo->run();
            if(is_array($msOptions) and count($msOptions)>0){
                foreach($msOptions as $msOption){
                    if(!$opt = $this->modx->getObject("fsOption",['class_id'=>3,'key'=>$msOption['key']])){
                        if($opt = $this->modx->newObject("fsOption",[
                            'class_id'=>3,
                            'key'=>$msOption['key'],
                            'option_native_id'=>$msOption['id'],
                            'alias'=>$msOption['key'],
                            'label'=>$msOption['caption'],
                            'active'=>0,
                            ])){
                                $opt->save();
                            }
                    }
                }
            }
            //msVendor
            if(!$opt = $this->modx->getObject("fsOption",['class_id'=>5,'key'=>'name'])){
                if($opt = $this->modx->newObject("fsOption",[
                    'class_id'=>5,
                    'key'=>'name',
                    'alias'=>'vendor',
                    'label'=>'Бренд',
                    'active'=>0,
                    ])){
                        $opt->save();
                    }
            }
        }
        //TV
        $this->pdo->setConfig([
            'class'=>'modTemplateVar',
            'return'=>'data',
            'limit'=>0,
            ]);
        $modTemplateVars = $this->pdo->run();
        if(is_array($modTemplateVars) and count($modTemplateVars)>0){
            foreach($modTemplateVars as $modTemplateVar){
                if(!$opt = $this->modx->getObject("fsOption",['class_id'=>4,'key'=>$modTemplateVar['key']])){
                    if($opt = $this->modx->newObject("fsOption",[
                        'class_id'=>4,
                        'key'=>$modTemplateVar['name'],
                        'option_native_id'=>$modTemplateVar['id'],
                        'alias'=>$modTemplateVar['name'],
                        'label'=>$modTemplateVar['caption'],
                        'active'=>0,
                        ])){
                            $opt->save();
                        }
                }
            }
        }
        return $this->fs->success();
    }
    
    public function upload_resources($data = [],$context = 'web') {
        if(!$this->config['enable_upload']) return $this->fs->error("not enable_upload");
        
        $last_upload = $this->modx->getOption('facetsearch_last_upload', null, '', true);
        $uploading = $this->fs->check_lock('facetsearch_uploading');
        if($uploading) return $this->fs->error("uploading");
        $stop_uploading = $this->fs->check_lock('facetsearch_stop_uploading');
        if($stop_uploading) return $this->fs->error("stop_uploading");
        $this->fs->lock('facetsearch_uploading', true);
        //return $this->fs->error("uploading1");

        $this->pdo->setConfig([
            'class'=>'fsOption',
            'where'=>['active'=>1],
            'return'=>'data',
            'limit'=>0,
            ]);
        $fsOptions = $this->pdo->run();
        if(!is_array($fsOptions) or count($fsOptions)==0){
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[FacetSearch] Empty fsOptions');
            $this->fs->lock('facetsearch_uploading', false);
            return $this->fs->error('[FacetSearch] Empty fsOptions');
        }
        $fsOptions0 = [];
        foreach($fsOptions as $fsOption){
            switch($fsOption['class_id']){
                case 1:
                    $key = 'resource';
                break;
                case 2:
                    $key = 'msProductData';
                break;
                case 3:
                    $key = 'msOption';
                break;
                case 4:
                    $key = 'TV';
                break;
                case 5:
                    $key = 'msVendor';
                break;
                
            }
            $fsOptions0[$key][] = $fsOption;
        }

        $c = $this->modx->newQuery('modResource');
        $where = ['context_key'=>$context,'published'=>1,'deleted'=>0];
        if(!empty($last_upload)){
            $last_upload = strtotime($last_upload);
            $where['editedon:>=']=$last_upload;
        }
        $c->where($where);
        $count = $this->modx->getCount('modResource',$c);
        //return $this->fs->success('count',['count'=>$count,'where'=>$where]);
        $step = 100;
        $i = 0;
        $success = true;
        
        while($i<$count){
            if($this->fs->check_lock('facetsearch_stop_uploading')){
                $this->fs->lock('facetsearch_uploading', false);
                return $this->fs->error('[FacetSearch] facetsearch_stop_uploading');
            }
            $c->limit($step,$i);
            $i += $step;
            $resources = $this->modx->getIterator('modResource',$c);
            $puts = [];
            try{
                foreach($resources as $resource){
                    $puts[] = $this->get_put($resource,$fsOptions0,$context);
                }
                //return $this->fs->success('123',['puts'=>$puts,'fsOptions0'=>$fsOptions0]);
                if(!empty($puts)){
                    $resp = $this->fs->request('puts',$puts);
                    //$this->modx->log(modX::LOG_LEVEL_ERROR, '[FacetSearch] upload'.print_r($resp,1).print_r($puts,1));
                    //return $this->fs->success('break',['resp'=>$resp]);
                    if(!$resp['success']){
                        $success = false;
                        $this->modx->log(modX::LOG_LEVEL_ERROR, '[FacetSearch] error upload puts '.print_r($puts,1));
                        $this->modx->log(modX::LOG_LEVEL_ERROR, '[FacetSearch] error upload '.print_r($resp,1));
                    }
                }
            } catch (Exception $e) {
                $this->modx->log(1,'[FacetSearch] upload_resources Выброшено исключение: ',  $e->getMessage(), "\n");
                break;
            }
            $procent = round($i / $count, 2) * 100;
            $this->fs->setOption('facetsearch_build_index_status', $procent);
        }
        
        if($success){
            $this->fs->setOption('facetsearch_last_upload', date('Y-m-d H:i:s'));
        }
        if(empty($last_upload)) $resp = $this->fs->request('index_builded',[]);
        //fsPubDelRes
        $success = $this->res_delete();
        $success = $this->res_undelete($fsOptions0,$context);
        
        $this->fs->lock('facetsearch_uploading', false);
        return $this->fs->success('123');
    }
    public function res_delete(){
        $this->pdo->setConfig([
            'class'=>'fsPubDelRes',
            'where'=>['active'=>1,'status_id'=>1],
            'return'=>'data',
            'limit'=>0,
            ]);
        $fsPubDelRess = $this->pdo->run();
        if(is_array($fsPubDelRess) and count($fsPubDelRess) >0){
            $ids = [];
            foreach($fsPubDelRess as $fsPubDelRes){
                if($fsPubDelRes0 = $this->modx->getObject('fsPubDelRes',$fsPubDelRes['id'])){
                    $fsPubDelRes0->active = false;
                    $fsPubDelRes0->save();
                }
                $ids[] = $fsPubDelRes['resource_id'];
            }
            $resp = $this->fs->request('delete_ids',$ids);
        }
        return true;
    }
    public function res_undelete($fsOptions0,$context){
        $this->pdo->setConfig([
            'class'=>'fsPubDelRes',
            'where'=>['active'=>1,'status_id'=>2],
            'return'=>'data',
            'limit'=>0,
            ]);
        $fsPubDelRess = $this->pdo->run();
        if(is_array($fsPubDelRess) and count($fsPubDelRess) >0){
            $ids = [];
            foreach($fsPubDelRess as $fsPubDelRes){
                if($fsPubDelRes0 = $this->modx->getObject('fsPubDelRes',$fsPubDelRes['id'])){
                    $fsPubDelRes0->active = false;
                    $fsPubDelRes0->save();
                }
                $ids[] = $fsPubDelRes['resource_id'];
            }
            $c = $this->modx->newQuery('modResource');
            $where = ['context_key'=>$context,'published'=>1,'deleted'=>0,'id:IN'=>$ids];
            $c->where($where);
            $count = $this->modx->getCount('modResource',$c);
            //return $this->fs->success('count',['count'=>$count,'where'=>$where]);
            $step = 100;
            $i = 0;
            $success = true;
            
            while($i<$count){
                if($this->fs->check_lock('facetsearch_stop_uploading')){
                    $this->fs->lock('facetsearch_uploading', false);
                    return $this->fs->error('[FacetSearch] Empty stop_uploading');
                }
                $c->limit($step,$i);
                $i += $step;
                $resources = $this->modx->getIterator('modResource',$c);
                $puts = [];
                try{
                    foreach($resources as $resource){
                        $puts[] = $this->get_put($resource,$fsOptions0,$context);
                    }
                    //return $this->fs->success('123',['puts'=>$puts,'fsOptions0'=>$fsOptions0]);
                    if(!empty($puts)){
                        $resp = $this->fs->request('puts',$puts);
                        //$this->modx->log(modX::LOG_LEVEL_ERROR, '[FacetSearch] upload'.print_r($resp,1).print_r($puts,1));
                        //return $this->fs->success('break',['resp'=>$resp]);
                        if(!$resp['success']){
                            $success = false;
                            $this->modx->log(modX::LOG_LEVEL_ERROR, '[FacetSearch] error upload puts '.print_r($puts,1));
                            $this->modx->log(modX::LOG_LEVEL_ERROR, '[FacetSearch] error upload '.print_r($resp,1));
                        }
                    }
                } catch (Exception $e) {
                    $this->modx->log(1,'[FacetSearch] upload_resources Выброшено исключение: ',  $e->getMessage(), "\n");
                    break;
                }
                $procent = round($i / $count, 2) * 100;
                $this->fs->setOption('facetsearch_build_index_status', $procent);
            }
        }
        return true;
    }
    public function get_put($resource,$fsOptions,$context = 'web'){
        $put = [
            'id'=>$resource->id,
            'parent'=>$resource->parent,
            'parent_ids'=>$this->modx->getParentIds($resource->id, 10, ['context' => $context]),
        ];
        //resource 1
        if(isset($fsOptions['resource'])){
            foreach($fsOptions['resource'] as $o){
                $put[$o['alias']] = $resource->get($o['key']);
            }
        }
        //msProductData 2
        if(isset($fsOptions['msProductData'])){
            if($msProductData = $this->modx->getObject('msProductData',$resource->id)){
                foreach($fsOptions['msProductData'] as $o){
                    $val = $msProductData->get($o['key']);
                    if(empty($val)) continue;
                    switch($o['option_type_id']){
                        case 1:
                            $put[$o['alias']] = $val;
                        break;
                        case 2:
                            $put[$o['alias']] = (float)str_replace(',','.',$val);
                        break;
                        case 3:
                            $arr = $val;
                            if(!empty($arr)){
                                foreach($arr as &$msv){
                                    $msv = (float)str_replace(',','.',$msv);
                                }
                                $put[$o['alias']] = $arr;
                            }
                        break;
                        case 4:
                            $arr = $val;
                            if(!empty($arr)){
                                $put[$o['alias']] = $arr;
                            }
                        break;
                    }
                }
            }
        }
        //msVendor 5
        if(isset($fsOptions['msVendor'])){
            if($msProductData = $this->modx->getObject('msProductData',$resource->id)){
                if($msVendor = $this->modx->getObject('msVendor',$msProductData->vendor)){
                    foreach($fsOptions['msVendor'] as $o){
                        $val = $msVendor->get($o['key']);
                        if(empty($val)) continue;
                        switch($o['option_type_id']){
                            case 1:
                                $put[$o['alias']] = $val;
                            break;
                            case 2:
                                $put[$o['alias']] = (float)str_replace(',','.',$val);
                            break;
                            case 3:
                                $arr = $val;
                                if(!empty($arr)){
                                    foreach($arr as &$msv){
                                        $msv = (float)str_replace(',','.',$msv);
                                    }
                                    $put[$o['alias']] = $arr;
                                }
                            break;
                            case 4:
                                $arr = $val;
                                if(!empty($arr)){
                                    $put[$o['alias']] = $arr;
                                }
                            break;
                        }
                    }
                }
            }
        }
        //msOption 3
        if(isset($fsOptions['msOption'])){
            foreach($fsOptions['msOption'] as $o){
                $sql = "SELECT `value` FROM {$this->modx->getTableName('msProductOption')} WHERE product_id = {$resource->id} AND `key` = '{$o['key']}'";
                $statement = $this->modx->query($sql);
                if($statement){
                    $values = $statement->fetchAll(PDO::FETCH_ASSOC);
                    if(empty($values)) continue;
                    switch($o['option_type_id']){
                        case 1:
                            $put[$o['alias']] = $values[0]['value'];
                        break;
                        case 2:
                            $put[$o['alias']] = (float)str_replace(',','.',$values[0]['value']);
                        break;
                        case 3:
                            foreach($values as $v){
                                $put[$o['alias']][] = (float)str_replace(',','.',$v['value']);
                            }
                        break;
                        case 4:
                            foreach($values as $v){
                                $put[$o['alias']][] = $v['value'];
                            }
                        break;
                    }
                }else{
                    $this->modx->log(1,"[FacetSearch] get_put error $sql");
                    throw new Exception('Не верный sql');
                }
            }
        }
        //TV 4
        if(isset($fsOptions['TV'])){
            foreach($fsOptions['TV'] as $o){
                if($tv = $this->modx->getObject('modTemplateVar', ['name'=>$o['key']])){
                    $tvr = $this->modx->getObject('modTemplateVarResource', [
                        'tmplvarid' => $tv->id,
                        'contentid' => $resource->id
                    ]);
                    if ($tvr) {
                        $values = $tvr->get('value');
                    }else if ($tv) $values = $tv->get('default_text');
                    
                    //$values  = $resource->getTVValue($o['key']);
                    if(!empty($values)){
                        switch($o['option_type_id']){
                            case 1:
                                $put[$o['alias']] = $values;
                            break;
                            case 2:
                                $put[$o['alias']] = (float)str_replace(',','.',$values);
                            break;
                            case 3:
                                $values = explode('||',$values);
                                foreach($values as $v){
                                    $put[$o['alias']][] = (float)str_replace(',','.',$v);
                                }
                            break;
                            case 4:
                                $values = explode('||',$values);
                                foreach($values as $v){
                                    $put[$o['alias']][] = $v;
                                }
                            break;
                        }
                    }
                }
            }
        }
        return $put;
    }
    public function getActiveOptions(){
        $this->pdo->setConfig([
            'class'=>'fsOption',
            'where'=>['active'=>1],
            'return'=>'data',
            'limit'=>0,
            ]);
        $fsOptions0 = $this->pdo->run();
        $fsOptions = [];
        foreach($fsOptions0 as $fsOption){
            $fsOptions[$fsOption['alias']] = $fsOption;
        }
        return $fsOptions;
    }
    public function pagination($request,$total){
        $fqn = $this->modx->getOption('pdoPage.class', null, 'pdotools.pdopage', true);
        $path = $this->modx->getOption('pdopage_class_path', null, MODX_CORE_PATH . 'components/pdotools/model/', true);
        if ($pdoClass = $this->modx->loadClass($fqn, $path, false, true)) {
            $pdoPage = new $pdoClass($this->modx, $this->config);
        } else {
            return false;
        }
        // Base url for pdoPage
        if ($this->modx->getOption('friendly_urls')) {
            $q_var = $this->modx->getOption('request_param_alias', null, 'q');
            $_REQUEST[$q_var] = $this->modx->makeUrl((int)$_REQUEST['pageId']);
        } else {
            $id_var = $this->modx->getOption('request_param_id', null, 'id');
            $_GET[$id_var] = (int)$_REQUEST['pageId'];
        }
        $url = $pdoPage->getBaseUrl();
        if(empty((int)$request['page'])){
            $page = 1;
        }else{
            $page = (int)$request['page'];
        }
        $offset = $page*$this->config['limit'];
        $pageCount = !empty($this->config['limit']) && $total > $offset
        ? ceil(($total) / $this->config['limit'])
        : 0;
        $tplPage = $this->config['tplPage'];
        $tplPageFirst = $this->config['tplPageFirst'];
        $tplPagePrev = $this->config['tplPagePrev'];
        $tplPageNext = $this->config['tplPageNext'];
        $tplPageLast = $this->config['tplPageLast'];

        $tplPageWrapper = $this->config['tplPageWrapper'];
        if (!empty($pageCount) && $pageCount > 1) {
            $pagination = [
                'first' => $page > 1 && !empty($tplPageFirst)
                    ? $pdoPage->makePageLink($url, 1, $tplPageFirst)
                    : '',
                'prev' => $page > 1 && !empty($tplPagePrev)
                    ? $pdoPage->makePageLink($url, $page - 1, $tplPagePrev)
                    : '',
                'pages' => $this->config['pageLimit'] >= 7 && empty($this->config['disableModernPagination'])
                    ? $pdoPage->buildModernPagination($page, $pageCount, $url)
                    : $pdoPage->buildClassicPagination($page, $pageCount, $url),
                'next' => $page < $pageCount && !empty($tplPageNext)
                    ? $pdoPage->makePageLink($url, $page + 1, $tplPageNext)
                    : '',
                'last' => $page < $pageCount && !empty($tplPageLast)
                    ? $pdoPage->makePageLink($url, $pageCount, $tplPageLast)
                    : '',
            ];
            if (!empty($pageCount)) {
                foreach (['first', 'prev', 'next', 'last'] as $v) {
                    $tpl = 'tplPage' . ucfirst($v) . 'Empty';
                    if (!empty($this->config[$tpl]) && empty($pagination[$v])) {
                        $pagination[$v] = $pdoPage->pdoTools->getChunk($this->config[$tpl]);
                    }
                }
            }
        } else {
            $pagination = array(
                'first' => '',
                'prev' => '',
                'pages' => '',
                'next' => '',
                'last' => ''
            );
        }
        return !empty($tplPageWrapper)
            ? $pdoPage->pdoTools->getChunk($tplPageWrapper, $pagination)
            : $pdoPage->pdoTools->parseChunk('', $pagination);
    }
    public function filter($data){
        $fsOptions = $this->getActiveOptions();
        $filters0 = array_map('trim', explode(',', $this->config['filters']));
        $filters = [];
        foreach($filters0 as $filter){
            $filter = explode(':',$filter);
            if(!isset($fsOptions[$filter[0]])) continue;
            if(empty($filter[1])) $filter[1] = 'default';
            $filters[$filter[0]] = $fsOptions[$filter[0]];
            $filters[$filter[0]]['type'] = $filter[1];
        }
        $resp = $this->get_results($data,$filters);
        $filters = $this->get_filters($data,$filters);
        $pagination = $this->pagination($data,$resp['data']['total']);
        
        $resp['data']['filters'] = $filters;
        $resp['data']['pagination'] = $pagination;
        if ($this->modx->user->hasSessionContext('mgr') && !empty($this->config['showLog'])) {
            $resp['data']['log'] = $this->fs->getTime();
        }
        return $resp;
    }
    public function filter_ajax($data){
        $fsOptions = $this->getActiveOptions();
        $filters0 = array_map('trim', explode(',', $this->config['filters']));
        $filters = [];
        foreach($filters0 as $filter){
            $filter = explode(':',$filter);
            if(!isset($fsOptions[$filter[0]])) continue;
            if(empty($filter[1])) $filter[1] = 'default';
            $filters[$filter[0]] = $fsOptions[$filter[0]];
            $filters[$filter[0]]['type'] = $filter[1];
        }
        $resp = $this->get_results($data,$filters);
        if(!isset($data['no_aggs']) or $data['no_aggs'] != 1){
            $aggs = $this->get_aggs($data,$filters);
        }
        $pagination = $this->pagination($data,$resp['data']['total']);
        
        $pages = !empty($this->config['limit']) && $resp['data']['total'] > $this->config['limit']
        ? ceil(($resp['data']['total']) / $this->config['limit'])
        : 0;
        if(empty((int)$data['page'])){
            $page = 1;
        }else{
            $page = (int)$data['page'];
        }
        $resp['data']['aggs'] = $aggs;
        $resp['data']['pagination'] = $pagination;
        $resp['data']['pages'] = $pages;
        $resp['data']['page'] = $page;
        if ($this->modx->user->hasSessionContext('mgr') && !empty($this->config['showLog'])) {
            $resp['data']['log'] = $this->fs->getTime();
        }
        return $resp;
    }
    public function get_aggs($request,$filters){
        
        $filters = $this->get_filters_aggs($request,$filters,false);
        $aggs = [];
        foreach($filters as $field=>$filter){
            switch($filter['type']){
                case 'number':
                    foreach($filter['vals'] as $val){
                        $aggs[$field][$val] = 1; 
                    }
                break;
                default:
                    foreach($filter['vals'] as $val){
                        $aggs[$field][$val['key']] = $val['doc_count']; 
                    }
            }
        }
        return $aggs;
    }
    public function get_filters_aggs($request,$filters){
        $this->fs->addTime('start get_filters_aggs');
        $query = $this->get_query_aggs($request,$filters);
        $resp = $this->fs->request('search',$query);
        $this->fs->addTime('end get_filters_aggs');
        //$this->fs->addTime('end get_filters_aggs'.print_r($resp,1));
        //$this->fs->addTime('end get_filters_aggs'.json_encode($query, JSON_PRETTY_PRINT));
        $aggregations = $resp['data']['outs']['aggregations'];
        
        foreach($filters as $field=>$filter){
            if(empty($aggregations[$field])){
                //unset($filters[$field]);
                continue;
            }
            foreach($aggregations[$field]['buckets'] as $bucket){
                $filters[$field]['vals'][$bucket['key']] = ['key' =>$bucket['key']];
            }
            //unset($aggregations[$field]);
        }
        
        if(isset($aggregations['inactive_filter_aggs'])){
            unset($aggregations['inactive_filter_aggs']['doc_count']);
            foreach($filters as $field=>$filter){
                if(!empty($aggregations['inactive_filter_aggs'][$field.'_min'])){
                    $filters[$field]['vals'][] = $aggregations['inactive_filter_aggs'][$field.'_min']['value'];
                    $filters[$field]['vals'][] = $aggregations['inactive_filter_aggs'][$field.'_max']['value'];
                }
            }
            foreach($aggregations['inactive_filter_aggs'] as $field=>$aggs){
                if(!isset($filters[$field])) continue;
                foreach($aggs['buckets'] as $bucket){
                    $filters[$field]['vals'][$bucket['key']] = $bucket;
                }
            }
            unset($aggregations['inactive_filter_aggs']);
        }
        $aggsRadio0 = array_map('trim', explode(',', $this->config['aggsRadio']));
        $aggsRadio = [];
        foreach($aggsRadio0 as $r){
            $aggsRadio[$r]=1;
        }
        foreach($filters as $field => $filter){
            if(!empty($request[$field])){
                //$this->fs->addTime('end get_aggs'.$field);
                if(isset($aggregations['active_filter_aggs_'.$field])){
                    unset($aggregations['active_filter_aggs_'.$field]['doc_count']);
                    foreach($aggregations['active_filter_aggs_'.$field] as $field1=>$aggs){
                        foreach($aggs['buckets'] as $bucket){
                            //$this->fs->addTime('end get_aggs bucket'.print_r($bucket,1));
            
                            if(empty($aggsRadio[$field1])){
                                if(!empty($bucket['doc_count'])) $bucket['doc_count'] = '+'.$bucket['doc_count'];
                            }else{
                                if(!empty($bucket['doc_count'])) $bucket['doc_count'] = $bucket['doc_count'];
                            }
                            $filters[$field1]['vals'][$bucket['key']] = $bucket;
                        }
                    }
                    unset($aggregations['active_filter_aggs_'.$field]);
                }
            }
        }
        return $filters;
    }
    public function get_filters($request,$filters){
        $filters = $this->get_filters_aggs($request,$filters);
        //$this->fs->addTime('end request'.print_r($request,1));
        $filters_out = [];
        foreach($filters as $field=>$filter){
            $request_vals = [];
            if(!empty($request[$field])){
                $request_vals = explode(',',$request[$field]);
            }
            
            if(!empty($filter['vals'])){
                switch($filter['type']){
                    case 'number':
                        $rows = []; $idx = 0;
                        $tplOuter = !empty($this->config['tplFilter.outer.' . $field])
                            ? $this->config['tplFilter.outer.' . $field]
                            : $this->config['tplFilter.outer.slider'];
                        $tplRow = !empty($this->config['tplFilter.row.' . $field])
                            ? $this->config['tplFilter.row.' . $field]
                            : $this->config['tplFilter.row.number'];
                        
                        foreach($filter['vals'] as $val){
                            $selected = in_array($val,$request_vals);
                            //$this->fs->addTime("$val $selected request_vals".print_r($request_vals,1));
                            
                            $rows[] = $this->pdo->getChunk($tplRow, [
                                'filter_key'=>$field,
                                'value'=>$val,
                                'title'=>$idx?'До':'От',
                                //'selected'=>$selected?'selected':'',
                                //'disabled'=>!$selected && empty($val['doc_count'])?'disabled':'',
                                'idx'=>$idx
                            ]);
                            $idx++;
                        }
                        $filter['rows'] = implode("\r\n",$rows);
                        if(empty($filter['label'])) $filter['label'] = 'facetsearch_field_'.$filter['alias'];
                        
                        $filters_out[$field] = $this->pdo->getChunk($tplOuter, $filter);    

                    break;    
                    default:
                        $rows = []; $idx = 0; $selected_empty = true;
                        $tplOuter = !empty($this->config['tplFilter.outer.' . $field])
                            ? $this->config['tplFilter.outer.' . $field]
                            : $this->config['tplFilter.outer.default'];
                        $tplRow = !empty($this->config['tplFilter.row.' . $field])
                            ? $this->config['tplFilter.row.' . $field]
                            : $this->config['tplFilter.row.default'];
                        foreach($filter['vals'] as $val){
                            $checked = in_array($val['key'],$request_vals);
                            if($checked) $selected_empty = false;
                            //$this->fs->addTime("{$val['key']} $checked request_vals".print_r($request_vals,1));
                            $title = $val['key'];
                            
                            switch($filter['option_type_id']){
                                case 2:case 3:
                                    $title = str_replace('.',',',$title);
                                break;
                            }
                            $rows[] = $this->pdo->getChunk($tplRow, [
                                'filter_key'=>$field,
                                'value'=>$val['key'],
                                'title'=>$title,
                                'num'=>$val['doc_count'],
                                'checked'=>$checked?'checked':'',
                                'selected'=>$checked?'selected':'',
                                'disabled'=>!$checked && empty($val['doc_count'])?'disabled':'',
                                'idx'=>$idx
                            ]);
                            $idx++;
                        }
                        $filter['rows'] = implode("\r\n",$rows);
                        if(empty($filter['label'])) $filter['label'] = $this->modx->lexicon('facetsearch_field_'.$filter['alias']);
                        if($selected_empty) $filter['selected'] = 'selected';
                        $filters_out[$field] = $this->pdo->getChunk($tplOuter, $filter);
                }
            }
        }
        return $filters_out;
    }
    
    public function get_query_aggs($request,$filters,$get_all_filters = true){
        $size = $this->config['aggs_size'];
        $main_filter=[]; $aggs = [];
        
        $main_filter[] = [
            'terms'=>[
                'parent_ids'=>array_map('trim', explode(',', $this->config['parents']))
            ]
        ];
        if($this->config['addFilters']){
            if(is_array($this->config['addFilters'])){
                $addFilters = $this->config['addFilters'];
            }else{
                $addFilters = json_decode($this->config['addFilters'],1);
            }
            foreach($addFilters as $field => $vals){
                $main_filter[] = [
                    'terms'=>[
                        $field=>array_map('trim', explode(',', $vals))
                    ]
                ];
            }
        }
        if($get_all_filters){
            foreach($filters as $field => $filter){
                switch($filter['type']){
                    case 'number':break;
                    default:
                        $aggs[$field] = ['terms'=>['field'=>$field,'size'=>$size]];
                }
            }
        }
        $inactive_filter = $main_filter;
        $inactive_fields = []; $active_fields = [];
        $aggsRadio0 = array_map('trim', explode(',', $this->config['aggsRadio']));
        $aggsRadio = [];
        foreach($aggsRadio0 as $r){
            $aggsRadio[$r]=1;
        }
        foreach($filters as $field => $filter){
            if(!empty($request[$field])){
                switch($filter['type']){
                    case 'number':
                        $range = explode(',',$request[$field]);
                        $inactive_filter[] = [
                            'range'=>[
                                $field=>['gt'=>$range[0],'lt'=>$range[1]]
                            ]
                        ];
                        $inactive_fields[] = $field;
                    break;
                    default:
                        $inactive_filter[$field] = [
                            'terms'=>[
                                $field=>explode(',',$request[$field])
                            ]
                        ]; 
                        $active_fields[] = $field;
                }
                
            }else{
                $inactive_fields[] = $field;
            }
        }
        if(!empty($inactive_fields)){
            $inactive_filter0 = [];
            foreach($inactive_filter as $v){ $inactive_filter0[] = $v;}
            $aggs['inactive_filter_aggs']=[
                'filter'=>['bool'=>['filter'=>$inactive_filter0]]
            ];
            foreach($inactive_fields as $field){
                switch($filters[$field]['type']){
                    case 'number':
                        $aggs['inactive_filter_aggs']['aggs'][$field.'_min'] = ['min'=>['field'=>$field]];
                        $aggs['inactive_filter_aggs']['aggs'][$field.'_max'] = ['max'=>['field'=>$field]];
                    break;
                    default:
                        $aggs['inactive_filter_aggs']['aggs'][$field] = ['terms'=>['field'=>$field,'size'=>$size]];
                }
                
            }
        }
        if(!empty($active_fields)){
            foreach($active_fields as $field){
                $active_filter = $inactive_filter;
                $must_not = $active_filter[$field];
                unset($active_filter[$field]);
                $active_filter0 = [];
                foreach($active_filter as $v){$active_filter0[]=$v;}
                if(empty($aggsRadio[$field])){
                    $aggs['active_filter_aggs_'.$field]=[
                        'filter'=>['bool'=>[
                            'filter'=>$active_filter0,
                            'must_not'=>$must_not
                            ]]
                    ];
                }else{
                    $aggs['active_filter_aggs_'.$field]=[
                        'filter'=>['bool'=>[
                            'filter'=>$active_filter0
                            ]]
                    ];
                }
                $aggs['active_filter_aggs_'.$field]['aggs'][$field] = ['terms'=>['field'=>$field,'size'=>$size]];
            }
        }
        $query = [
            'size'=>0,
            'query'=>[
                'bool'=>[
                    'filter'=>$main_filter
                ]
            ],
            'aggs'=>$aggs
        ];
        
        return $query;
    }
    public function get_results($data,$filters){
        

        $this->fs->addTime('start search');
        $query = $this->get_query($data,$filters);
        $resp = $this->fs->request('search',$query);
        $this->fs->addTime('end search');
        //$this->fs->addTime('get_search'.print_r($resp,1).json_encode($query, JSON_PRETTY_PRINT));
        if(!$resp['success'] or empty($resp['data']['outs']['hits']['total']['value'])){
            return $this->fs->success('',['total'=>0,'results'=>'Подходящих результатов не найдено.']);
        }
        $total = $resp['data']['outs']['hits']['total']['value'];
        $result_ids = [];
        foreach($resp['data']['outs']['hits']['hits'] as $hits){
            $result_ids[] = $hits['_id'];
        }
        //$results = implode('<br>',$result_ids);

        $elementName = $this->config['element'];
        $elementSet = array();
        if (strpos($elementName, '@') !== false) {
            list($elementName, $elementSet) = explode('@', $elementName);
        }
        /** @var modSnippet $snippet */
        if (!empty($elementName) && $element = $this->modx->getObject('modSnippet', array('name' => $elementName))) {
            $elementProperties = $element->getProperties();
            $elementPropertySet = !empty($elementSet)
                ? $element->getPropertySet($elementSet)
                : array();
            if (!is_array($elementPropertySet)) {$elementPropertySet = array();}
            $params = array_merge(
                $elementProperties,
                $elementPropertySet,
                $this->config
            );
            $params['resources'] = implode(',',$result_ids);
            $params['parents'] = '0';
            $element->setCacheable(false);
            $results = $element->process($params);
            $this->fs->addTime('end element');
        }else{
            $this->fs->addTime('filter not element');
        }
        return $this->fs->success('',['total'=>$total,'results'=>$results]);
    }
    public function get_query($request,$filters){
        $query_filter=[];
        $query_filter[] = [
            'terms'=>[
                'parent_ids'=>array_map('trim', explode(',', $this->config['parents']))
            ]
        ];
        if($this->config['addFilters']){
            if(is_array($this->config['addFilters'])){
                $addFilters = $this->config['addFilters'];
            }else{
                $addFilters = json_decode($this->config['addFilters'],1);
            }
            foreach($addFilters as $field => $vals){
                $query_filter[] = [
                    'terms'=>[
                        $field=>array_map('trim', explode(',', $vals))
                    ]
                ];
            }
        }
        foreach($filters as $field => $filter){
            if(!empty($request[$field])){
                switch($filter['type']){
                    case 'number':
                        $range = explode(',',$request[$field]);
                        $query_filter[] = [
                            'range'=>[
                                $field=>['gt'=>$range[0],'lt'=>$range[1]]
                            ]
                        ];
                    break;
                    default:
                        $query_filter[] = [
                            'terms'=>[
                                $field=>explode(',',$request[$field])
                            ]
                        ]; 
                }
            }
        }

        $query = [
            '_source'=>['_id'],
            'size'=>$this->config['limit'],
            'track_total_hits'=>true,
            'query'=>[
                'bool'=>[
                    'filter'=>$query_filter
                ]
            ]
        ];
        $sort = false;
        if($this->config['sort']){
            $sort = $this->config['sort'];
        }
        if($request['sort']){
            $sort = $request['sort'];
        }
        if($sort){
            $sorts = explode(',',$sort);
            foreach($sorts as $sort){
                $sort = explode(':',$sort);
                $query['sort'] = [[$sort[0]=>['order'=>$sort[1]]]];
            }
        }
        if(!empty((int)$request['page'])){
            $query['from'] = (int)$request['page']*$this->config['limit'];
        }
        
        return $query;
    }
    /*
    GET mftest.loc/_search
    {
    "query": {
        "bool": {
        "filter": [ 
            { "terms":  { "parent_ids": [5] }},
            { 
            "range": {
                "size": {
                "gte": 0,
                "lte": 60
                }
            }
            }
        ]
        }
    }
    }
    */
    
}