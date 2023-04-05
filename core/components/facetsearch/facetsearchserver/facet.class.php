<?php
class facet{
    public $client;
    public $config;
    public function __construct($config) {
        $this->config = $config;
        $this->client = (new \OpenSearch\ClientBuilder())
            ->setHosts([$config['host']])
            ->setBasicAuthentication($config['login'], $config['password']) // For testing only. Don't store credentials in code.
            ->setSSLVerification(false) // For testing only. Use certificate for validation
            ->build();
    }
    public function puts($data = []){
        if(empty($data['index']) or empty($data['data'])) return $this->error('Empty data');
        $puts = json_decode($data['data'],1);
        $outs = [];
        //return $this->success('1111',['puts'=>$puts,'outs'=>$outs]);
        $params = ['body' => []];
        foreach($puts as $put){
            $id = $put['id']; unset($put['id']);
            $params['body'][] = [
                'index' => [
                    '_index' => $data['index'],
                    '_id'    => $id
                ]
            ];

            $params['body'][] = $put;
        }
        try {
            $outs = $this->client->bulk($params);
        } catch (Exception $e) {
            return $this->error('Выброшено исключение: puts '.$puts.print_r($data,1).print_r($params,1),['error'=>  $e->getMessage(). "\n"]);
        }
        return $this->success('',['outs'=>$outs]);
    }
    public function delete_index($data = []){
        if(empty($data['index'])) return $this->error('Empty index');
        $outs = [];
        try {
            $outs = $this->client->indices()->delete([
                'index' => $data['index']
            ]);
        } catch (Exception $e) {
            return $this->error('Выброшено исключение: ',['error'=>  $e->getMessage(). "\n"]);
        }
        return $this->success('',['outs'=>$outs]);
    }
    public function create_index($data = []){
        if(empty($data['index'])) return $this->error('Empty index');
        $outs = [];
        try {
            $outs = $this->client->indices()->create([
                'index' => $data['index']
            ]);
        } catch (Exception $e) {
            return $this->error('Выброшено исключение: ',['error'=>  $e->getMessage(). "\n"]);
        }
        return $this->success('',['outs'=>$outs]);
    }
    public function mapping_index($data = []){
        if(empty($data['index'])) return $this->error('Empty index');
        $body = json_decode($data['data'],1);
        $outs = [];
        try {
            $outs = $this->client->indices()->putMapping([
                'index' => $data['index'],
                'body'=>$body
            ]);
        } catch (Exception $e) {
            return $this->error('Выброшено исключение: ',['error'=>  $e->getMessage(). "\n"]);
        }
        return $this->success('',['outs'=>$outs]);
    }
    public function search($data = []){
        if(empty($data['index'])) return $this->error('Empty index');
        $body = json_decode($data['data'],1);
        $outs = [];
        try {
            $outs = $this->client->search([
                'index' => $data['index'],
                'body'=>$body
            ]);
        } catch (Exception $e) {
            return $this->error('Выброшено исключение: ',['error'=>  $e->getMessage(). "\n"]);
        }
        return $this->success('',['outs'=>$outs]);
    }
    
    public function delete_ids($data = []){
        if(empty($data['index']) or empty($data['data'])) return $this->error('Empty data');
        $ids = json_decode($data['data'],1);
        $outs = [];
        $params = ['body' => []];
        foreach($ids as $id){
            $params['body'][] = [
                'delete' => [
                    '_index' => $data['index'],
                    '_id'    => $id
                ]
            ];
        }
        try {
            $outs = $this->client->bulk($params);
        } catch (Exception $e) {
            return $this->error('Выброшено исключение: ',['error'=>  $e->getMessage(). "\n"]);
        }
        return $this->success('',['outs'=>$outs]);
    }
    private function get_alias($action,$data = []){
        if(file_exists(__DIR__ . '/secret/aliases.json')){
            $aliases = json_decode(file_get_contents(__DIR__ . '/secret/aliases.json'),1);
            if(isset($aliases[$data['index']])){
                if($aliases[$data['index']]['builded'] == 1){
                    return $aliases[$data['index']]['index'];
                }else{
                    switch($action){
                        case 'puts': case 'delete_ids':case 'mapping_index':
                            return $aliases[$data['index']]['index'];
                        break;
                        default:
                            return $aliases[$data['index']]['index_old'];
                    }
                }
            }
        }
        return $data['index'];
    }
    private function rebuild_index($data = []){
        $outs = [];
        if(file_exists(__DIR__ . '/secret/aliases.json')){
            $aliases = json_decode(file_get_contents(__DIR__ . '/secret/aliases.json'),1);
            if(isset($aliases[$data['index']])){
                if($aliases[$data['index']]['builded']){
                    $old_aliases = $aliases[$data['index']]['index'];
                }else{
                    try {
                        $outs[] = $this->client->indices()->delete([
                            'index' => $aliases[$data['index']]['index']
                        ]);
                    } catch (Exception $e) {
                        return $this->error('Выброшено исключение: ',['error'=>  $e->getMessage(). "\n"]);
                    }
                    $old_aliases = $aliases[$data['index']]['index_old'];
                }
            }else{
                $old_aliases = $data['index'];
            }
            
        }else{
            $aliases = [];
            $old_aliases = $data['index'];
        }
        $new_aliases = $data['index'].'_'.date('Y_m_d_H_i_s');
        
        try {
            $outs[] = $this->client->indices()->create([
                'index' => $new_aliases
            ]);
        } catch (Exception $e) {
            return $this->error('Выброшено исключение: ',['error'=>  $e->getMessage(). "\n"]);
        }
        $aliases[$data['index']] = [
            'builded' => 0,
            'index' => $new_aliases,
            'index_old'=>$old_aliases
        ];
        $success = file_put_contents(__DIR__ . '/secret/aliases.json',json_encode($aliases));
        return $this->success('',['outs'=>$outs,'success'=>$success]);
    }
    private function index_builded($data = []){
        $outs = [];
        if(file_exists(__DIR__ . '/secret/aliases.json')){
            $aliases = json_decode(file_get_contents(__DIR__ . '/secret/aliases.json'),1);
            if(isset($aliases[$data['index']])){
                try {
                    $outs[] = $this->client->indices()->delete([
                        'index' => $aliases[$data['index']]['index_old']
                    ]);
                } catch (Exception $e) {
                    //return $this->error('Выброшено исключение: ',['error'=>  $e->getMessage(). "\n"]);
                }
                $aliases[$data['index']]['builded'] = 1;
                file_put_contents(__DIR__ . '/secret/aliases.json',json_encode($aliases));
            }
        }
        
        return $this->success('',['outs'=>$outs]);
    }

    public function run(){
        if($this->config['index'] != $data['index'] or $this->config['api_key'] != $data['api_key']) 
            return $this->error('Access denied!'.$access);

        if($_POST['action'] == 'rebuild_index') return $this->rebuild_index($_POST);
        if($_POST['action'] == 'index_builded') return $this->index_builded($_POST);
        
        $_POST['index'] = $this->get_alias($_POST['action'],$_POST);	

        switch($_POST['action']){
            case 'puts':
                return $this->puts($_POST);
            break;
            case 'delete_ids':
                return $this->delete_ids($_POST);
            break;
            case 'delete_index':
                return $this->delete_index($_POST);
            break;
            case 'create_index':
                return $this->create_index($_POST);
            break;
            case 'mapping_index':
                return $this->mapping_index($_POST);
            break;
            case 'search':
                return $this->search($_POST);
            break;
        }
        return $this->success('hello',$_POST);
    }
    public function success($message = "",$data = []){
        return array('success'=>1,'message'=>$message,'data'=>$data);
    }
    public function error($message = "",$data = []){
        return array('success'=>0,'message'=>$message,'data'=>$data);
    }
}