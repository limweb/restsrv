<?php
namespace Jacwright\RestServer; 
require_once __DIR__.'/../libs/DbTrait.php';
require_once __DIR__.'/../libs/Nonce.php';
require_once __DIR__.'/../libs/Csrf.php';

class  RestController {

		public function __construct() {
			consolelog('-----construct----first function----------------');
			// $hostName = explode(':',$_SERVER['HTTP_HOST'])[0];
			// $file = @file_get_contents('http://127.0.0.1:8000/license.txt');
			// $file = @file_get_contents(__DIR__.'/license.txt');
			// if(strpos($file,$hostName) == false){
			// 	exit("Domain [$hostName] not registered. Please contact (limweb@hotmail.com) for a license");
			// }
			$this->jwt = (new Restjwt());
			$this->rbac = (new RestRbac($this->jwt));
			$this->get =  (object) $_GET;
			$this->post = (object) $_POST;
		}

		public function init() {
			consolelog('-----init------second function --------------');
			// dump($this->jwt->tokenverify());
			// dump($this->jwt->chkauth());
		}

		public function authorize(){
			consolelog('-----Authorize------third function --------------');
			$chk = 0;
			if($this->jwt) {
				$this->jwt->server = $this->server;
				$chk =  $this->jwt->chkauth();
			}
			// dump($chk);
			return $chk;
    	}




}