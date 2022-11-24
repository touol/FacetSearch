<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
class FacetSearch
{
    /** @var modX $modx */
    public $modx;
    public $pdo;
    public $miniShop2;
    /** @var array() $config */
    public $config = [];
    public $fsHandler = null;
    
    public $timings = [];
    protected $start = 0;
    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = [])
    {
        $this->modx =& $modx;
        $corePath = MODX_CORE_PATH . 'components/facetsearch/';
        $assetsUrl = MODX_ASSETS_URL . 'components/facetsearch/';

        
        $this->config = array_merge([
            'corePath' => $corePath,
            'classesPath' => $corePath . 'classes/',
            'modelPath' => $corePath . 'model/',
            'enable_upload'=>$this->modx->getOption('facetsearch_enable_upload', null, false, true),
            'index'=>$this->modx->getOption('facetsearch_index', null, '', true),
            'server_url'=>$this->modx->getOption('facetsearch_server_url', null, '', true),
            'api_key'=>$this->modx->getOption('facetsearch_api_key', null, '', true),
            'aggs_size'=>$this->modx->getOption('facetsearch_aggs_size', null, 500, true),
            'limit'=>10,
            
        ], $config);

        $this->modx->addPackage('facetsearch', $this->config['modelPath']);
        $this->modx->lexicon->load('facetsearch:default');

        $this->miniShop2 = $modx->getService('miniShop2');

        if ($this->pdo = $this->modx->getService('pdoFetch')) {
            $this->pdo->setConfig($this->config);
        }
        $this->timings = [];
        $this->time = $this->start = microtime(true);

        $this->loadClasses();
        $this->loadHandler();
    }
    /**
     * Add new record to time log
     *
     * @param $message
     * @param null $delta
     */
    public function addTime($message, $delta = null)
    {
        $time = microtime(true);
        if (!$delta) {
            $delta = $time - $this->time;
        }

        $this->timings[] = array(
            'time' => number_format(round(($delta), 7), 7),
            'message' => $message,
        );
        $this->time = $time;
    }
    /**
     * Return timings log
     *
     * @param bool $string Return array or formatted string
     *
     * @return array|string
     */
    public function getTime($string = true)
    {
        $this->timings[] = array(
            'time' => number_format(round(microtime(true) - $this->start, 7), 7),
            'message' => '<b>Total time</b>',
        );
        $this->timings[] = array(
            'time' => number_format(round((memory_get_usage(true)), 2), 0, ',', ' '),
            'message' => '<b>Memory usage</b>',
        );

        if (!$string) {
            return $this->timings;
        } else {
            $res = '';
            foreach ($this->timings as $v) {
                $res .= $v['time'] . ': ' . $v['message'] . "\n";
            }

            return $res;
        }
    }
    public function loadClasses()
	{
		$classesPath = $this->config['classesPath'];
		if (file_exists($classesPath) && $files = scandir($classesPath)) {
			foreach ($files as $file) {
				if (preg_match('#\.class\.php$#i', $file)) {
					/** @noinspection PhpIncludeInspection */
					include $classesPath . '/' . $file;
				}
			}
		} else {
			$this->modx->log(modX::LOG_LEVEL_ERROR, "[FacetSearch] Classes path is not exists: \"{$classesPath}\"");
		}
	}
    /**
     * Loads custom filters handler class
     *
     * @return bool
     */
    public function loadHandler() {
        if (!is_object($this->fsHandler)) {
            $filters_class = $this->modx->getOption('facetsearch_handler_class', null, 'FacetSearchHandler', true);
            if (!class_exists($filters_class)) {
                $filters_class = 'FacetSearchHandler';
            }

            $this->fsHandler = new $filters_class($this, $this->config);
            if (!($this->fsHandler instanceof FacetSearchHandler)) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, '[FacetSearch] Could not initialize filters handler class: "' . $filters_class . '"');

                return false;
            }
        }
        return true;
    }
    public function upload_resources($data = [],$context = 'web') {
        return call_user_func_array([$this->fsHandler, 'upload_resources'],[$data]);
    }
    public function check_lock($lock_set){
        return file_exists($this->config['corePath'].$lock_set.'.lock');
    }
    public function lock($lock_set,$lock){
        if($lock){
            file_put_contents($this->config['corePath'].$lock_set.'.lock','1');
        }else{
            unlink($this->config['corePath'].$lock_set.'.lock');
        }
    }
    public function rebuild_index(){
        $this->lock('facetsearch_stop_uploading', true);
        $uploading = $this->check_lock('facetsearch_uploading');
        $i = 0;
        while($i < 40 and $uploading){
            sleep(1);
            $uploading = $this->check_lock('facetsearch_uploading');
            $i++;
        }
        if($uploading) return $this->error("Не удалось останавить загрузку индекса!");
        //$resp = $this->request('delete_index',[]);
        $this->setOption('facetsearch_last_upload', '');
        
        $resp = $this->mapping_index();
        if(!$resp['success']){
            $resp['message'] = "Не удалось обновить индекс!";
            return $resp;
        } 
        $this->lock('facetsearch_stop_uploading', false);
        return $this->success("Ребилд начат");
    }
    public function create_index(){
        $this->lock('facetsearch_stop_uploading', true);
        $uploading = $this->check_lock('facetsearch_uploading');
        $i = 0;
        while($i < 40 and $uploading){
            sleep(1);
            $uploading = $this->check_lock('facetsearch_uploading');
            $i++;
        }
        if($uploading) return $this->error("Не удалось останавить загрузку индекса!");
        //$resp = $this->request('delete_index',[]);
        $resp = $this->request('create_index',[]);
        $this->setOption('facetsearch_last_upload', '');
        
        $resp = $this->mapping_index();
        if(!$resp['success']){
            $resp['message'] = "Не удалось создать индекс!";
            return $resp;
        } 
        $this->lock('facetsearch_stop_uploading', false);
        return $this->success("Индекс создан");
    }
    
    public function delete_index(){
        $this->lock('facetsearch_stop_uploading', true);
        $uploading = $this->check_lock('facetsearch_uploading');
        $i = 0;
        while($i < 40 and $uploading){
            sleep(1);
            $uploading = $this->check_lock('facetsearch_uploading');
            $i++;
        }
        if($uploading) return $this->error("Не удалось останавить загрузку индекса!");
        $resp = $this->request('delete_index',[]);
        //$this->setOption('facetsearch_last_upload', '');
        
        //$resp = $this->mapping_index();
        if(!$resp['success']){
            $resp['message'] = "Не удалось удалить индекс!";
            return $resp;
        } 
        //$this->lock('facetsearch_stop_uploading', false);
        return $this->success("Удалено");
    }
    public function mapping_index(){
        $this->pdo->setConfig([
            'class'=>'fsOption',
            'where'=>['active'=>1],
            'return'=>'data',
            'limit'=>0,
            ]);
        $fsOptions = $this->pdo->run();
        if(!is_array($fsOptions) or count($fsOptions)==0){
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[FacetSearch] Empty fsOptions');
            $this->setOption('facetsearch_uploading', false);
            return $this->error('[FacetSearch] Empty fsOptions');
        }
        $properties = [];
        $properties['all_properties'] = [
            'type' => 'text'
        ];
        foreach($fsOptions as $fsOption){
            $type = 'keyword';
            switch($fsOption['option_type_id']){
                case 2:
                    $type = 'float';
                break;
                // case 3: case 4:
                //     $type = 'keyword';
                // break;
            }
            $properties[$fsOption['alias']] = [
                'type' => $type,
                'copy_to'=>'all_properties'
            ];
        }
        $resp = $this->request('mapping_index',[            
            'properties' =>$properties]);
        $resp['data']['json'] = json_encode( [            
            'properties' =>$properties],JSON_PRETTY_PRINT);
        return $resp;
    }
    public function request($action,$data = array()){
        return $this->curl($action,$data);
    }
    public function setOption($option,$value){
        if($option = $this->modx->getObject('modSystemSetting', $option)){
            $option->set('value', $value);
            $option->save();
            //Чистим кеш
            $this->modx->cacheManager->refresh(array('system_settings' => array()));
        }
    }
    public function curl($action,$data = array()){
        $ch = curl_init($this->config['server_url']);
        $send = [
            'action'=>$action,
            'index'=>$this->config['index'],
            'api_key'=>$this->config['api_key'],
            'data'=>json_encode($data),
        ];
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $send); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        
        $res0 = json_decode($res, 1);
        if(!isset($res0['success'])){
            return $this->error('error curl',['res'=>$res]);
        }
        return $res0;
    }
    public function build_index_status($data){
        sleep(1);
        return $this->success('',[
            'completed'=>false,
            'procent'=>$this->modx->getOption('facetsearch_build_index_status', null, '0', true),
            'offset'=>1,
            'message'=>"Статус"]);
    }
    public function handleRequest($action, $data = array())
    {
        
        //set_time_limit(3000);
        $data = $this->modx->sanitize($data, $this->modx->sanitizePatterns);
        
        switch($action){
            case 'get_options': case 'filter':case 'filter_ajax':
                if (!method_exists($this->fsHandler, $action)) {
                    return $this->success("Method $action not exists!");
                }
                return call_user_func_array([$this->fsHandler, $action],[$data]);
            break;
            case 'puts':
                return $this->upload_resources();
            break;
            case 'rebuild_index':
                return $this->rebuild_index();
            break;
            case 'create_index':
                return $this->create_index();
            break;
            case 'delete_index':
                return $this->delete_index();
            break;
            case 'build_index_status':
                return $this->build_index_status($data);
            break;
            default:
                return $this->error("Not found action $action! {$data['trs_data'][0]['id']}".print_r($data,1));
        }
    }
    public function success($message = "",$data = []){
        return array('success'=>1,'message'=>$message,'data'=>$data);
    }
    public function error($message = "",$data = []){
        return array('success'=>0,'message'=>$message,'data'=>$data);
    }
}