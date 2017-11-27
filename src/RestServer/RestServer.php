<?php
////////////////////////////////////////////////////////////////////////////////
//
// Copyright (c) 2009 Jacob Wright
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.
//
////////////////////////////////////////////////////////////////////////////////
namespace Servit\RestServer;

require_once __DIR__.'/../Libs/utils.php';

use Servit\RestServer\RestFormat;
use Servit\RestServer\RestException;
use Servit\RestServer\RestJwt;
use Servit\RestServer\RestRbac;
use Servit\RestServer\RestController;
use Servit\Libs\Request;
use Exception;
use ReflectionClass;
use ReflectionObject;
use ReflectionMethod;
use DOMDocument;
use Illuminate\Database\Capsule\Manager as Capsule;
//------------- INIT----------------------------------------
// Optional array of authorized client IPs for a bit of security
$config["hostsAllowed"] = [];

if (!function_exists( 'implodeKV' )) {
    function implodeKV($glueKV, $gluePair, $KVarray)  {
      if( is_object($KVarray) ) {
        $KVarray = json_decode(json_encode($KVarray),TRUE);
      }
      $t = array();
      foreach($KVarray as $key=>$val) {
        if(is_array($val)){
          $val = implodeKV(':',',',$val);
        }else if( is_object($val)){
          $val = json_decode(json_encode($val),TRUE);
          $val = implodeKV(':',',',$val);
        }

        if(is_int($key)){
          $t[] = $val;
        } else {
          $t[] = $key . $glueKV . $val;
        }
      }
      return implode($gluePair, $t);
    }
}

if (!function_exists('consolelog') ) {
    function consolelog($status = 200)  {
      $lists = func_get_args();
      $status = '';
      $status = implodeKV( ':' , ' ' , $lists);
      if(isset($_SERVER["REMOTE_ADDR"]) && !empty($_SERVER["REMOTE_ADDR"])){
        $raddr =$_SERVER["REMOTE_ADDR"];
      } else {
        $raddr = '127.0.0.1';
      }
      if(isset($_SERVER["REMOTE_PORT"]) && !empty($_SERVER["REMOTE_PORT"])){
        $rport = $_SERVER["REMOTE_PORT"];
      } else {
        $rport = '8000';
      }

      if(isset($_SERVER["REQUEST_URI"]) && !empty($_SERVER["REQUEST_URI"])){
        $ruri = $_SERVER["REQUEST_URI"];
      } else {
        $ruri = '/console';
      }
      file_put_contents("php://stdout",
        sprintf("[%s] %s:%s [%s]:%s \n",
          date("D M j H:i:s Y"),
          $raddr,$rport,
          $status,
          $ruri
          )
        );
    }
} // end-of-check funtion exist

if(!function_exists( 'logAccess' )){
  function logAccess($status = 200) {
    file_put_contents("php://stdout", sprintf("[%s] %s:%s [%s]: %s\n",
      date("D M j H:i:s Y"), $_SERVER["REMOTE_ADDR"],
      $_SERVER["REMOTE_PORT"], $status, $_SERVER["REQUEST_URI"]));
  }
}
//------------- INIT----------------------------------------

/**
 * Description of RestServer
 *
 * @author jacob
 */
class RestServer {
	//@todo add type hint
	public $url;
	public $method;
	public $params;
	public $format;
	public $cacheDir = __DIR__;
	public $realm;
	public $mode;
	public $root;
	public $rootPath;
	public $serverpath;
	public $jsonAssoc = false;
	protected $map = array();
	protected $errorClasses = array();
	protected $cached;
	private $_token = null ;  // string payload  header.payload.sinager

	/**
	 * The constructor.
	 *
	 * @param string $mode The mode, either debug or production
	 */
	public function  __construct(\Servit\Cfg\Config $config = null,$mode = 'debug', $realm = 'Rest Server'){
		$this->mode = $mode;
		$this->realm = $realm;
		// Set the root
		$dir = str_replace('\\', '/', dirname(str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME'])));
		if ($dir == '.') {
			$dir = '/';
		} else {
			// add a slash at the beginning and end
			if (substr($dir, -1) != '/') $dir .= '/';
			if (substr($dir, 0, 1) != '/') $dir = '/' . $dir;
		}
		$this->server = $_SERVER;
		if($config){
			$this->config = $config;
		} else {
			$this->config = new Config();
		}
		$this->root = $dir;
		$this->serverpath = glob($_SERVER["DOCUMENT_ROOT"])[0];
	}

	public function  __destruct()	{
		if ($this->mode == 'production' && !$this->cached) {
			if (function_exists('apc_store')) {
				apc_store('urlMap', $this->map);
			} else {
				file_put_contents($this->cacheDir . '/urlMap.cache', serialize($this->map));
			}
		}
	}

	// for /routes only to view  any route in this system
	public function routes(){
		return $this->map;
	}

	public function setToken($token=null) {
		if($token) $this->_token = $token;
	}	
	public function getToken() {
		return  $this->_token;
	}

	public function refreshCache() 	{
		$this->map = array();
		$this->cached = false;
	}

	public function unauthorized($ask = false) 	{
		if ($ask) {
			header("WWW-Authenticate: Basic realm=\"$this->realm\"");
		}
		throw new RestException(401, "You are not authorized to access this resource.");
	}

	public function options() 	{
		if(CROS){
			return ['status'=>'success'];
		}  throw new RestException(200, "authorized");
	}

	public function handle() {
		$this->url = $this->getPath();
		$this->method = $this->getMethod();
		$this->format = $this->getFormat();
		$this->data = $this->getData();
		if ($this->method == 'PUT' || $this->method == 'POST' || $this->method == 'PATCH') {
			// $this->data = $this->getData();
		}

		//preflight requests response 
		if($this->method == 'OPTIONS' && isset(getallheaders()['Access-Control-Request-Headers'])){
			$this->sendData($this->options());
		} 

		list($obj, $method, $params, $this->params, $noAuth) = $this->findUrl();
		if ($obj) {
			if (is_string($obj)) {
				if (class_exists($obj)) {
					$obj = new $obj();
				} else {
					throw new RestException("Class $obj does not exist");
				}
			}

			$obj->server = $this;

			try {
				if (method_exists($obj, 'init')) {
					$obj->init();
				}

				if (!$noAuth && method_exists($obj, 'authorize')) {
					if (!$obj->authorize()) {
						$this->sendData($this->unauthorized(true)); //@todo unauthorized returns void
						exit;
					}
				}

				$result = call_user_func_array(array($obj, $method), $params);

				if ($result !== null) {
					$this->sendData($result);
				}
			} catch (RestException $e) {
				$this->handleError($e->getCode(), $e->getMessage());
			}

		} else {
			$this->handleError(404);
		}
	}
	public function setRootPath($path) 	{
		$this->rootPath = '/'.trim($path, '/').'/';
	}
	public function setJsonAssoc($value) {
		$this->jsonAssoc = ($value === true);
	}

	public function addClass($class, $basePath = '') {
		$path = glob($_SERVER["DOCUMENT_ROOT"])[0];
		$filepath = $path.'/controllers/'.$class.'.php';
		if (file_exists($filepath)) {
			require_once($filepath);
			$this->loadCache();
			if (!$this->cached) {
				if (is_string($class) && !class_exists($class)){
					throw new RestException('Invalid method or class');
				} elseif (!is_string($class) && !is_object($class)) {
					throw new RestException('Invalid method or class; must be a classname or object');
				}

				if (substr($basePath, 0, 1) == '/') {
					$basePath = substr($basePath, 1);
				}
				if ($basePath && substr($basePath, -1) != '/') {
					$basePath .= '/';
				}
				$this->generateMap($class, $basePath);
			}
		}
	}

	public function addErrorClass($class)	{
		$this->errorClasses[] = $class;
	}

	public function handleError($statusCode, $errorMessage = null)	{
		$method = "handle$statusCode";
		foreach ($this->errorClasses as $class) {
			if (is_object($class)) {
				$reflection = new ReflectionObject($class);
			} elseif (class_exists($class)) {
				$reflection = new ReflectionClass($class);
			}
			if (isset($reflection))
			{
				if ($reflection->hasMethod($method))
				{
					$obj = is_string($class) ? new $class() : $class;
					$obj->$method();
					return;
				}
			}
		}

		if (!$errorMessage)
		{
			$errorMessage = $this->codes[$statusCode];
		}

		$this->setStatus($statusCode);
		$this->sendData(array('error' => array('code' => $statusCode, 'message' => $errorMessage)));
	}

	protected function loadCache()	{
		if ($this->cached !== null) {
			return;
		}

		$this->cached = false;

		if ($this->mode == 'production') {
			if (function_exists('apc_fetch')) {
				$map = apc_fetch('urlMap');
			} elseif (file_exists($this->cacheDir . '/urlMap.cache')) {
				$map = unserialize(file_get_contents($this->cacheDir . '/urlMap.cache'));
			}
			if (isset($map) && is_array($map)) {
				$this->map = $map;
				$this->cached = true;
			}
		} else {
			if (function_exists('apc_delete')) {
				apc_delete('urlMap');
			} else {
				@unlink($this->cacheDir . '/urlMap.cache');
			}
		}
	}

	protected function findUrl() {
		$urls = $this->map[$this->method];
		if (!$urls) return null;
		foreach ($urls as $url => $call) {
			$args = $call[2];
			if(@$this->url[strlen($this->url)-1] == '/') {
				$url .= '/';
			}

			if (!strstr($url, '$')) {
				if ($url == $this->url) {
					if (isset($args['data'])) {
						$params = array_fill(0, $args['data'] + 1, null);
						$params[$args['data']] = $this->data;   //@todo data is not a property of this class
						$call[2] = $params;
					} else {
						$call[2] = array();
					}
					return $call;
				}
			} else {
				$regex = preg_replace('/\\\\\$([\w\d]+)\.\.\./', '(?P<$1>.+)', str_replace('\.\.\.', '...', preg_quote($url)));
				$regex = preg_replace('/\\\\\$([\w\d]+)/', '(?P<$1>[^\/]+)', $regex);
				if (preg_match(":^$regex$:", urldecode($this->url), $matches)) {
					$params = array();
					$paramMap = array();
					if (isset($args['data'])) {
						$params[$args['data']] = $this->data;
					}

					foreach ($matches as $arg => $match) {
						if (is_numeric($arg)) continue;
						$paramMap[$arg] = $match;

						if (isset($args[$arg])) {
							$params[$args[$arg]] = $match;
						}
					}
					ksort($params);
					// make sure we have all the params we need
					end($params);
					$max = key($params);
					for ($i = 0; $i < $max; $i++) {
						if (!array_key_exists($i, $params)) {
							$params[$i] = null;
						}
					}
					ksort($params);
					$call[2] = $params;
					$call[3] = $paramMap;
					return $call;
				}
			}
		}
	}

	protected function generateMap($class, $basePath)	{
		if (is_object($class)) {
			$reflection = new ReflectionObject($class);
		} elseif (class_exists($class)) {
			$reflection = new ReflectionClass($class);
		}
		

		$methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);    //@todo $reflection might not be instantiated
		foreach ($methods as $method) {
			if(!in_array($method->name,['init','__construct','authorize','__call','__destruct'])) {
				$doc = $method->getDocComment();
				$noAuth = strpos($doc, '@noAuth') !== false;
				
				$params = $method->getParameters();
				if (preg_match_all('/@url[ \t]+(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)[ \t]+\/?(\S*)/s', $doc, $matches, PREG_SET_ORDER)) {
					foreach ($matches as $match) {
						$httpMethod = $match[1];
						$url = $basePath . $match[2];
						if ($url && $url[strlen($url) - 1] == '/') {
							$url = substr($url, 0, -1);
						}
						$call = array($class, $method->getName());
						$args = array();
						foreach ($params as $param) {
							$args[$param->getName()] = $param->getPosition();
						}
						$call[] = $args;
						$call[] = null;
						$call[] = $noAuth;

						$this->map[$httpMethod][$url] = $call;
					}
				} else  {
					$chk = 1;
					$httpmethods = ['get','post','put','patch','delete','head','options'];
					foreach ($httpmethods as $httpMethod ) {
						$match = preg_split('@(?=[A-Z])@', $method->getName());
						if( $match[0]== $httpMethod ){
								$chk = 0;
								$url = strtolower($basePath.$match[1]);
								if ($url && $url[strlen($url) - 1] == '/') {
									$url = substr($url, 0, -1);
								}
								
								$args = array();
								foreach ($params as $param) {
									$args[$param->getName()] = $param->getPosition();
								}

								$call = array($class, $method->getName());
								$call[] = $args;
								$call[] = null;
								$call[] = $noAuth;
								$this->map[strtoupper($httpMethod)][$url] = $call;
								foreach ($args as $key => $value) {
									$call = array($class, $method->getName());
									$url .= '/$'.$key;
									$call[] = $args;
									$call[] = null;
									$call[] = $noAuth;
									$this->map[strtoupper($httpMethod)][$url] = $call;
								}
						}
					}

					if($chk){
						$url = strtolower($basePath.$method->getName());
						if ($url && $url[strlen($url) - 1] == '/') {
							$url = substr($url, 0, -1);
						}

						$args = array();
						foreach ($params as $param) {
							$args[$param->getName()] = $param->getPosition();
						}
							$call = array($class, $method->getName());
							$call[] = $args;
							$call[] = null;
							$call[] = $noAuth;
							$this->map['GET'][$url] = $call;
						foreach ($args as $key => $value) {
							$call = array($class, $method->getName());
							$url .= '/$'.$key;
							$call[] = $args;
							$call[] = null;
							$call[] = $noAuth;
							$this->map['GET'][$url] = $call;
						}
					}
				}
			}
		}
	}

	public function getPath() {
		$path = preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);
		// remove root from path
		if ($this->root) $path = preg_replace('/^' . preg_quote($this->root, '/') . '/', '', $path);
		// remove trailing format definition, like /controller/action.json -> /controller/action
		$path = preg_replace('/\.(\w+)$/i', '', $path);
		// remove root path from path, like /root/path/api -> /api
		if ($this->rootPath) $path = str_replace($this->rootPath, '', $path);
		return $path;
	}

	public function getMethod() {
		$method = $_SERVER['REQUEST_METHOD'];
		$override = isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) ? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] : (isset($_GET['method']) ? $_GET['method'] : '');
		if ($method == 'POST' && strtoupper($override) == 'PUT') {
			$method = 'PUT';
		} elseif ($method == 'POST' && strtoupper($override) == 'DELETE') {
			$method = 'DELETE';
		} elseif ($method == 'POST' && strtoupper($override) == 'PATCH') {
            $method = 'PATCH';
        }
		return $method;
	}

	public function getFormat() {
		$format = RestFormat::JSON;
		$accept_mod = null;
		if(isset($_SERVER["HTTP_ACCEPT"])) {
			$accept_mod = preg_replace('/\s+/i', '', $_SERVER['HTTP_ACCEPT']); // ensures that exploding the HTTP_ACCEPT string does not get confused by whitespaces
		}
		$accept = explode(',', $accept_mod);
		$override = '';

		if (isset($_REQUEST['format']) || isset($_SERVER['HTTP_FORMAT'])) {
			// give GET/POST precedence over HTTP request headers
			$override = isset($_SERVER['HTTP_FORMAT']) ? $_SERVER['HTTP_FORMAT'] : '';
			$override = isset($_REQUEST['format']) ? $_REQUEST['format'] : $override;
			$override = trim($override);
		}

		// Check for trailing dot-format syntax like /controller/action.format -> action.json
		if(preg_match('/\.(\w+)$/i', strtok($_SERVER["REQUEST_URI"],'?'), $matches)) {
			$override = $matches[1];
		}

		// Give GET parameters precedence before all other options to alter the format
		$override = isset($_GET['format']) ? $_GET['format'] : $override;
		if (isset(RestFormat::$formats[$override])) {
			$format = RestFormat::$formats[$override];
		} elseif (in_array(RestFormat::JSON, $accept)) {
			$format = RestFormat::JSON;
		}
		return $format;
	}

	public function getData() 	{
		return new Request($this->jsonAssoc);
	}

	public function sendData($data){
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: 0");
		header("Access-Control-Allow-Credentials: true");
		header('Access-Control-Max-Age: 1000');
		header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Access-Control-Allow-Headers, Authorization, X-Requested-With');
		// header("Access-Control-Allow-Headers: *");
        header('Access-Control-Expose-Headers: Authorization');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		// header('Content-Type: application/json; charset=utf-8');
		if($this->_token){
			Header('Authorization: '.$this->_token);
			// header('Authorization: Bearer '.$this->token);
			// Header('X-Authorization: '.$this->token);
			// Header('authorizations: '.$this->token);
			// Header('Authorization: '.$this->token);
		}

		header('Content-Type: ' . $this->format);
		
		if ($this->format == RestFormat::XML) {

				if (is_object($data) && method_exists($data, '__keepOut')) {
						$data = clone $data;
						foreach ($data->__keepOut() as $prop) {
							unset($data->$prop);
						}
					}
					$this->xml_encode($data);
		} else {
			if (is_object($data) && method_exists($data, '__keepOut')) {
				$data = clone $data;
				foreach ($data->__keepOut() as $prop) {
					unset($data->$prop);
				}
			}
			$options = 0;
			if ($this->mode == 'debug' && defined('JSON_PRETTY_PRINT')) {
				$options = JSON_PRETTY_PRINT;
			}

			if (defined('JSON_UNESCAPED_UNICODE')) {
				$options = $options | JSON_UNESCAPED_UNICODE;
			}

			echo json_encode($data, $options);
			exit();
		}
	}

	public function setStatus($code){ 
		consolelog('set Status----->http_response_code--->',$code);
		if (function_exists('http_response_code')) {
			http_response_code($code);
		} else {
			$protocol = $_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
			$code .= ' ' . $this->codes[strval($code)];
			header("$protocol $code");
		}
	}


	/**
	 * @param prefix
	 * @param dbname
	 * @param host
	 * @param username
	 * @param password
	 */ 

	public function setConnection($prefix='',$dbname=null,$host=null,$username=null,$password=null,$charset='utf8',$collation='utf8_unicode_ci') {
		$config = $this->config->dbconfig;
		$config['database'] = ( $dbname ?: DB_NAME );
		$config['prefix'] = ( $prefix ?:'');
		$config['host']	= ($host ?: DB_HOST);
		$config['username']	= ($username ?: DB_USER);
		$config['password']	= ($password ?: DB_PASSWORD);
		$config['charset'] =$charset;
		$config['collation'] =$collation;
		
		$capsule = new Capsule;
		$capsule->addConnection($config, 'default');
		$capsule->bootEloquent();
		$this->config->dbconfig = $config;
		// Capsule::setTablePrefix($prefix);
		// echo Capsule::getTablePrefix();
		// Capsule::setTablePrefix('sys_');
		// echo Capsule::getTablePrefix();
		// $this->server->setconnection() use in controller 
	}

	private function xml_encode($mixed, $domElement=null, $DOMDocument=null) {  //@todo add type hint for $domElement and $DOMDocument
		if (is_null($DOMDocument)) {
			$DOMDocument =new DOMDocument;
			$DOMDocument->formatOutput = true;
			$this->xml_encode($mixed, $DOMDocument, $DOMDocument);
			echo $DOMDocument->saveXML();
		}
		else {
			if (is_array($mixed)) {
				foreach ($mixed as $index => $mixedElement) {
					if (is_int($index)) {
						if ($index === 0) {
							$node = $domElement;
						}
						else {
							$node = $DOMDocument->createElement($domElement->tagName);
							$domElement->parentNode->appendChild($node);
						}
					}
					else {
						$plural = $DOMDocument->createElement($index);
						$domElement->appendChild($plural);
						$node = $plural;
						if (!(rtrim($index, 's') === $index)) {
							$singular = $DOMDocument->createElement(rtrim($index, 's'));
							$plural->appendChild($singular);
							$node = $singular;
						}
					}

					$this->xml_encode($mixedElement, $node, $DOMDocument);
				}
			}
			else {
				$domElement->appendChild($DOMDocument->createTextNode($mixed));
			}
		}
	}
	private $codes = array(
		'100' => 'Continue',
		'200' => 'OK',
		'201' => 'Created',
		'202' => 'Accepted',
		'203' => 'Non-Authoritative Information',
		'204' => 'No Content',
		'205' => 'Reset Content',
		'206' => 'Partial Content',
		'300' => 'Multiple Choices',
		'301' => 'Moved Permanently',
		'302' => 'Found',
		'303' => 'See Other',
		'304' => 'Not Modified',
		'305' => 'Use Proxy',
		'307' => 'Temporary Redirect',
		'400' => 'Bad Request',
		'401' => 'Unauthorized',
		'402' => 'Payment Required',
		'403' => 'Forbidden',
		'404' => 'Not Found',
		'405' => 'Method Not Allowed',
		'406' => 'Not Acceptable',
		'409' => 'Conflict',
		'410' => 'Gone',
		'411' => 'Length Required',
		'412' => 'Precondition Failed',
		'413' => 'Request Entity Too Large',
		'414' => 'Request-URI Too Long',
		'415' => 'Unsupported Media Type',
		'416' => 'Requested Range Not Satisfiable',
		'417' => 'Expectation Failed',
		'500' => 'Internal Server Error',
		'501' => 'Not Implemented',
		'503' => 'Service Unavailable'
	);
}