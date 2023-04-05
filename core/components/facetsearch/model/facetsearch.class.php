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
        if(file_exists($this->config['corePath'].$lock_set.'.lock')){
            $time_lock = (int)file_get_content($this->config['corePath'].$lock_set.'.lock');
            if(time() - $time_lock > 3600){
                unlink($this->config['corePath'].$lock_set.'.lock');
                return false;
            }else{
                return true;
            }
        }else{
            return false;
        }
    }
    public function lock($lock_set,$lock){
        if($lock){
            file_put_contents($this->config['corePath'].$lock_set.'.lock',time());
        }else{
            if(file_exists($this->config['corePath'].$lock_set.'.lock')) unlink($this->config['corePath'].$lock_set.'.lock');
        }
    }
    
    
    public function setOption($option,$value){
        if($option = $this->modx->getObject('modSystemSetting', $option)){
            $option->set('value', $value);
            $option->save();
            //Чистим кеш
            $this->modx->cacheManager->refresh(array('system_settings' => array()));
        }
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
    public function unDeleteChildren($parent) {
        $success = false;

        $kids = $this->modx->getCollection('modResource',array(
            'parent' => $parent,
        ));

        if(count($kids) > 0) {
            /* the resource has children resources, we'll need to undelete those too */
            /** @var modResource $kid */
            foreach ($kids as $kid) {
                if($kid->get('deleted') == 0){
                    if(!$fsPubDelRes = $modx->getObject('fsPubDelRes',['resource_id'=>$kid->get('id')])){
                        $fsPubDelRes = $modx->newObject('fsPubDelRes',['resource_id'=>$kid->get('id')]);
                    }
                    if($fsPubDelRes){
                        $fsPubDelRes->status_id = 2;
                        $fsPubDelRes->active = true;
                        $success = $fsPubDelRes->save();
                        if ($success) {
                            $success = $this->unDeleteChildren($kid->get('id'));
                        }
                    }
                }
            }
        }
        return $success;
    }
}