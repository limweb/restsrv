<?php
namespace Servit\Libs;

class Request  {

	protected $input ='';
	private $jsonAssoc = false;

	public function __construct($jsonAssoc){
		$this->jsonAssoc = $jsonAssoc;
		$this->input = new \stdClass();
		$this->getInput();
		$this->getGet();
		$this->getPost();
		$this->getFiles();
		$this->getHeader();
	}

	public function getInput(){
		$data = file_get_contents('php://input');
		$data = json_decode($data,$this->jsonAssoc);
		return $data;
	}

	public function getPost(){
		foreach ($_POST as $key => $value) {
			$this->input->{$key} = filter_input(INPUT_POST,$key);
		}
	}

	public function getGet(){
		foreach ($_GET as $key => $value) {
			$this->input->{$key} = filter_input(INPUT_GET,$key);
		}
	}


	public function getFiles(){

	}

	public function getHeader() {

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
	    	$posttoken = filter_input(INPUT_POST,REFTOKEN);
	    	if($posttoken) return $posttoken;

	    	$gettoken = filter_input(INPUT_GET,REFTOKEN);
	    	if($gettoken) return $gettoken;

	    	$data = file_get_contents('php://input');

			$data = json_decode($data);
			if(isset($data->ref_token)) return $data->{REFTOKEN};
	    }
	    return null;
	}


}