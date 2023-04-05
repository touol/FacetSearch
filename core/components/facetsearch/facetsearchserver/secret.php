<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
//require __DIR__ . '/vendor/autoload.php';
class facet_secret{
	public function put_secret($data = []){
		file_put_contents(__DIR__ . '/secret/secret.json',$_POST['secret']);
		return $this->success('',[]);
	}
	public function run(){
		if($_POST['secret_key'] != 'JmXQt0wff7wNcsGn1VoZcYjj4Tef1F6oOhWuRhVy') return $this->error('Не верный ключ!');
		switch($_POST['action']){
			case 'put_secret':
				return $this->put_secret($_POST);
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
$facet_secret = new facet_secret();
echo json_encode($facet_secret->run());