<?php
namespace Servit\Libs;
use Servit\Cfg\Config;

class Request  extends Config {

	private $jsonAssoc = false;

	public function __construct($jsonAssoc){
		parent::__construct();
		$this->jsonAssoc = $jsonAssoc;
		$this->input();
		$this->inputget();
		$this->inputpost();
		$this->inputfiles();
		$this->header();
		return $this;
	}

	private function input(){
		$data = file_get_contents('php://input');
		$data = json_decode($data,$this->jsonAssoc);
		dump($data);
		$this->input = new Config($data);
	}

	private function inputpost(){
		$o = new Config();
		foreach ($_POST as $key => $value) {
			$o->{$key} = filter_input(INPUT_POST,$key);
		}
		$this->inputpost  = $o;
	}

	public function inputget(){
		$o = new Config();
		foreach ($_GET as $key => $value) {
			$o->{$key} = filter_input(INPUT_GET,$key);
		}
		$this->inputget = $o;
	}


	public function inputfiles(){
		$o = new Config();
		foreach ($_FILES as $key => $value) {
			$o->{$key} = $value;
		}
		$this->inputfiles = $o;
	}

	public function header() {
		$this->header = $this->getBearerToken();
		$this->token =  $this->getBearerToken();
	}


	/** 
	 * Get hearder Authorization
	 * */
	private function getAuthorizationHeader(){
	        $headers = null;
	        if (isset($_SERVER['Authorization'])) {
	            $headers = trim($_SERVER["Authorization"]);
	        }
	        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
	            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
	        } elseif (function_exists('apache_request_headers')) {
	            $requestHeaders = apache_request_headers();
	            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
	            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
	            //print_r($requestHeaders);
	            if (isset($requestHeaders['Authorization'])) {
	                $headers = trim($requestHeaders['Authorization']);
	            }
	        }
	        return $headers;
	}

	/**
	 * get access token from header
	 * */
	private function getBearerToken() {
	    $headers = $this->getAuthorizationHeader();
	    // HEADER: Get the access token from the header
	    // /Bearer\s((.*)\.(.*)\.(.*))/
	    if (!empty($headers)) {
	        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
	            return $matches[1];
	        }
	    } else {
	    	if($this->inputget->{REFTOKEN}) return $this->inputget->{REFTOKEN};
	    	if($this->inputpost->{REFTOKEN}) return $this->inputpost->{REFTOKEN};
	    	if($this->input->{REFTOKEN}) return $this->input->{REFTOKEN};
	    }
	    return null;
	}


}