<?php

set_error_handler( 'custom_warning_handler', E_WARNING);
function custom_warning_handler($errno, $errstr) {
    // Prevent First Create Domain Cause SESSEION by Admin Instead of OwnerPermission
    $check = preg_match('/O_RDWR/', $errstr);
    if($errno==2 && $check ) {
        // echo $errstr.'<br>';
        // echo $_COOKIE['PHPSESSID'];
        unset($_COOKIE['PHPSESSID']);
        setcookie('PHPSESSID', null, -1, '/');
        echo "<script> location.href='/'; </script>";
    }
    
}


require_once __DIR__.'/../config/configdb.php';
// require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/DbTrait.php';
require_once __DIR__.'/HtmlTrait.php';
require_once __DIR__.'/SingletonTrait.php';
require_once __DIR__.'/SocketTrait.php';
require_once __DIR__.'/Javascript.php';
require_once __DIR__.'/JwtTrait.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model as Model;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
//============================= Server Start =======================================

//` javascript multiline     
class RestfulServer {

        //----- RestServer -----------------
            //@todo add type hint
            public $url;
            // public $method;
            public $params;
            // public $format;
            public $cacheDir = __DIR__;
            public $realm;
            public $mode;
            public $root;
            public $rootPath;
            public $jsonAssoc = false;
            protected $restdata;
            protected $map = array();
            protected $errorClasses = array();
            protected $cached;
        //----- RestServer -----------------

        // ----- config ---------------------------
            protected $usedb = false;   // use Eloquent db
            protected $useSocket = false; // Use Socket io with Elephone 
            protected $useJs = false;
            protected $useJwt     = false;
            protected $isroot = false;
            protected $hasroot = false;
            protected $isdebug = false;   // debuf
            protected $showqry = false;
            private   $start_time;  // use with debug to save total time
            private   $version = '0.0.0.2/2016-10-11/multilang';
            protected $languages = [];
            protected $language = '';
            protected $defaultlang = '';
            protected $langmessages = [];
            protected $route = '';
            protected $fullroute = [];
            protected $routepath = [];
            protected $routepages = [
                ];
                // 'products'=>'ProductService',
                // 'about'=>'AboutService',
                // 'productdetail'=>'ProductdetailService',
                // 'blog'=>'BlogService',
                // 'contact'=>'ContactService',
                // 'services'=>'SrvService',
                // 'portfolio' => 'PortfolioService',
                // 'contact-us' => 'ContactService',
                // 'pricing'=>'PricingService',
                // '404'=>'S404Service',
                // 'shortcodes'=>'ShortcodeService',
            protected $classname = '';
            //----Server var -----------------------------
                protected $host = '/';  
                protected $file = __FILE__;
                protected $server = null;
                protected $method  = null;
                protected $request  = null;
                protected $qrystr     = null;
                protected $input   = null;
                protected $inputarr = [];
                protected $qrypath = null;
                protected $reqs = [];
                protected $uri = [];
                protected $posts = [];
                protected $gets = [];
                protected $files = [];
                protected $isajax = false;
                protected $showtime = false;
            //----Server var -----------------------------

            // session & cookie
                protected $sessiones = null;
                protected $cookie = [];
                protected $maxtime = 0;  //mins time of session 0 = no use
                protected $authtype = 'session';   //  session   jwt  cookie
            // session & cookie

            //---jwt--------------------    
                protected $privateKey = null; // new Key('file://../config/pv4096.key');
                protected $publicKey  = null; // new Key('file://../config/pu4096.pub');
                protected $signer     = null; //new Sha256();
            //---jwt--------------------    
            
            protected $format = null;  // null  xml
            protected $loginpath = '/index.php';
            protected $useslug = 1;  // 0 not use  1  use slug = permalink  permalink() function 
            protected $apikey = 'youapikey';
            protected $response = [
            'code' =>0,
            'status' => 404,
            'data' => null,
            ];
            
            //htmluse
            //<h1ml>
            //<htmlhead>
            //<header>
            //<css>
            //<js>
            //<body>
            //<footer>
            //<htmlfooter>
            //not use -----------------------------------------------
                protected $css = '';
                protected $js = '';
                protected $jslast = '';
                protected $navbar = '';
                protected $header = '';
                protected $content = '';
                protected $footer ='';
                protected $htmlhead = '';
                protected $htmlfooter = '</body></html>';
                protected $appopt = '';
                protected $menu = '';
                protected $bladengine='';  // laravel blad template 
            //not use -----------------------------------------------
            
            protected $production = 0;  // 1 dev 0 production  
            
            protected $javascript;  // use with Javascript.php

            protected $backendbase = 'wordpress';  // use with HtmlService login();


            // Define HTTP responses
            // 200 => 'OK',
            // 400 => 'Bad Request',
            // 401 => 'Unauthorized',
            // 403 => 'Forbidden',
            // 404 => 'Not Found'
            protected $http_response_code = [
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
            ];
            // Define whether an HTTPS connection is required
            protected $HTTPS_required = FALSE;
            // Define whether user authentication is required
            protected $authentication_required = FALSE;
            // Define API response codes and their related HTTP response

            protected $api_response_code = [
            0 => ['HTTP Response' => 400, 'Message' => 'Unknown Error'],
            1 => ['HTTP Response' => 200, 'Message' => 'Success'],
            2 => ['HTTP Response' => 403, 'Message' => 'HTTPS Required'],
            3 => ['HTTP Response' => 401, 'Message' => 'Authentication Required'],
            4 => ['HTTP Response' => 401, 'Message' => 'Authentication Failed'],
            5 => ['HTTP Response' => 404, 'Message' => 'Invalid Request'],
            6 => ['HTTP Response' => 400, 'Message' => 'Invalid Response Format'],
            ];

            protected  $methodget = [];
            protected  $methodput = [];
            protected  $methodpost = [];
            protected  $methoddelete = [];
            protected  $reservemethod =[
            'getIndex',            
            'getcreate',
            'getShow',
            'getEdit',
            'putUpdate',
            'postStore',
            'deleteDestroy',
            'getRoutes',
            'getAll',
            'postLists'
            ];
         // ----- config ---------------------------

        // --------- Trait user -------------------    
            use Singleton;
            use HtmlTrait;
            use JwtTrait;      
            use DbTrait;       
            use SocketTrait;   
        // --------- Trait user -------------------    

    public function __construct($mode = 'debug', $realm = 'Rest Server') {

            ( session_status() == PHP_SESSION_NONE ?  session_start() : null );
            ( strtolower(filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest'  ?  $this->isajax = true : $this->isajax = false );
            //------------------------- SESSION TIME OUT ----------------------
            if($this->maxtime > 0 ) {
                if ( time() <  $_SESSION['session_time'] + $this->maxtime){
                    $_SESSION['session_time'] = time();
                }else{
                    session_destroy();
                    session_start();
                    $_SESSION['session_time'] = time();
                }
            }

            //------------------------- SESSION TIME OUT ----------------------
            $this->host = 'http://'.$_SERVER['HTTP_HOST'];
            // $this->host = 'http://tmt.ap.ngrok.io';
            $this->classname = get_class($this);

            //--------------------- JWT --- JSON WEB TOKEN -----------------------------
             $path = __DIR__.'/../config/';
            if($this->useJwt){
                $this->privateKey = new Key('file://'.$path.'pv4096.key');
                $this->publicKey  = new Key('file://'.$path.'pu4096.pub');
                $this->signer     = new Sha256();
                $uuid = bin2hex(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM));
                ( $this->jwtchk() ? $this->updateJwtcookie() : null);
            }
            //--------------------- JWT --- JSON WEB TOKEN -----------------------------
            $this->cookie =  $_COOKIE;
            $this->sessiones = $_SESSION;
            $this->server = $_SERVER;
            $this->posts = $_POST;
            $this->gets = $_GET;
            $this->login();
            $cache = __DIR__.'/cache';
            // $this->appopt = Appopt::get();
            // $this->menu = Template::where('parent','0')->where('status','1')->orderBy('pageorder','asc')->get();
            // $compiler  = new DbBladeCompiler(new \Illuminate\Filesystem\Filesystem(), $cache);
            // $this->bladengine = new \Illuminate\View\Engines\CompilerEngine($compiler);
            // $this->run();
            ( $this->usedb ? $this->makeModel() : null );
            ( $this->useSocket ? $this->setConnection() : null ); //socket io 

            //--------------- Rest Server ------------------ start ---
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
                $this->root = $dir;
            //--------------- Rest Server ------------------ end -----
            $this->restdata = new Restdata();
            $this->restdata->aaaa = 'aaaaa';
            $this->restdata->bbbb = 'testbbbb';
            $this->languages = json_decode(Lang::where('status',1)->orderBy('sort','asc')->get()->toJson());
            $this->langmessages = Langmessage::get();
            // $this->languages = json_decode('[{"id":1,"name":"\u0e44\u0e17\u0e22","pic":null,"status":1,"sort":1,"ref":"lang1","default":0,"updated_at":"2016-11-08 14:59:20","created_at":"2016-11-08 14:59:20"},{"id":2,"name":"english","pic":null,"status":1,"sort":2,"ref":"lang2","default":1,"updated_at":"2016-11-08 14:59:20","created_at":"2016-11-08 14:59:20"},{"id":3,"name":"\u65e5\u672c\u306e","pic":null,"status":1,"sort":3,"ref":"lang3","default":0,"updated_at":"2016-11-08 14:59:20","created_at":"2016-11-08 14:59:20"},{"id":4,"name":"\u4e2d\u6587","pic":null,"status":1,"sort":4,"ref":"lang4","default":0,"updated_at":"2016-11-08 14:59:20","created_at":"2016-11-08 14:59:20"}]');
            if(empty($this->languages)){
                $this->languages = json_decode('[{"id":1,"name":"lang","pic":null,"status":1,"sort":1,"ref":"lang1","default":1,"updated_at":"2016-11-08 14:59:20","created_at":"2016-11-08 14:59:20"}]');
            }

            $this->_init();
    }

    public  function __destruct() {
        if(!$this->production){
            if(!$this->isajax){
                if($this->isdebug) {
                    $this->format = null;
                    if($this->usedb && $this->showqry){
                        $this->dump(Capsule::getQueryLog());
                    }
                    $mic_time = microtime();
                    $mic_time = explode(" ",$mic_time);
                    $mic_time = $mic_time[1] + $mic_time[0];
                    $endtime = $mic_time;
                    $total_execution_time = ($endtime - $this->start_time);
                    if($this->showtime){
                        echo "<br>Total Executaion Time ".$total_execution_time." seconds";
                    }
                }
            }
        }

        // ----------------- Rest Server ------------------------ start ----
        if ($this->mode == 'production' && !$this->cached) {
            if (function_exists('apc_store')) {
                apc_store('urlMap', $this->map);
            } else {
                file_put_contents($this->cacheDir . '/urlMap.cache', serialize($this->map));
            }
        }
        // ----------------- Rest Server ------------------------ end ------

    }

    private  function starttime() {
        $mic_time = microtime();
        $mic_time = explode(" ",$mic_time);
        $mic_time = $mic_time[1] + $mic_time[0];
        $this->start_time = $mic_time;
    }

    public function  getGet(){
        foreach ($this->methodget as $get) {
            $get =(object) $get;
            if(empty($this->request)){ $this->request[0] = ''; }
            if(strtolower($get->path) == strtolower($this->request[0])){
                array_shift($this->request);
                if($this->request){
                    call_user_func_array([$this,$get->method],$this->request);
                } else {
                    call_user_func_array([$this,$get->method],[]);
                }
                return;
            }
        }
        if($this->useslug) {
            call_user_func_array([$this,'permalink'],$this->request);
        }
    }

    public function permalink($id=null) {
        try {
            if($this->usedb && $this->model && $id){
                    $o = new stdClass();
                    $rs = $this->model->find($id);
                    $o->data = $rs;
                    $o->input = $this->request;
                    $this->response($o,'json');
            } else {
                $this->routeroot('get');
            }
        } catch (Exception $e) {
            if($this->isajax){
                $this->rest_error(-1,$e,'json',0,__FILE__); //or
            } else {
                $this->rest_error(-1,$e,'html',$e->getCode());                       
            }
        }
    }

    protected function init_route(){
            if($this->isroot){
                $this->fullroute =  $this->request;
            }
            // $this->dump($this->fullroute);
            $this->setStatus(200);

            $chklang = 1;
            foreach ($this->languages as $lang) {

                if($lang->default == 1 ) {
                    $this->defaultlang = $lang;
                }
            }    

            foreach ($this->languages as $lang) {
                if(urldecode($this->uri[0]) == $lang->name){
                    // $this->language = $this->uri[0];
                    $this->language = $lang;
                    $this->setSess('lang',$this->language);
                    (isset($this->uri[1]) ?  $this->route = urldecode($this->uri[1]) : null );
                    ($this->route == '' ? $this->route = '/': null );
                    $this->routepathp[] = $this->uri[0];
                    array_shift($this->uri);
                    array_shift($this->request);
                    $chklan=0;
                    break;
                }
            }

            if($chklang){
                if($this->isroot && $this->hasroot){
                    $this->language = $this->languages[0];
                    $this->setSess('lang',$this->language);
                } else  {
                    if(isset($this->sessiones['lang'])){
                        $this->language = $this->sessiones['lang'];
                        $changdefault  =1 ;
                        foreach ($this->languages as $lang) {
                            if($this->language->name == $lang->name) {
                                $changdefault  = 0;
                                break;
                            }
                        }

                        if($changdefault ){
                            $this->language = $this->languages[0];
                            $this->setSess('lang',$this->language);
                        }

                    } else {
                        $this->language = $this->languages[0];
                        $this->setSess('lang',$this->language);
                    }
                }
                (isset($this->uri[0]) ?  $this->route = $this->uri[0] : null );
                ($this->route == '' ? $this->route = '/': null );

                if(isset($this->sessiones['backlang'])){
                    $this->backlang = $this->sessiones['backlang'];
                } else {
                    $this->backlang = $this->language;
                    $this->setSess('backlang',$this->backlang);
                }
            }

            // if (in_array($this->uri[0],(array) $this->languages)) {
            //     $this->language = $this->uri[0];
            //     $this->setSess('lang',$this->language);
            //     (isset($this->uri[1]) ?  $this->route = $this->uri[1] : null );
            //     ($this->route == '' ? $this->route = '/': null );
            //     $this->routepathp[] = $this->uri[0];
            //     array_shift($this->uri);
            //     array_shift($this->request);
            // } else {
            //     if($this->isroot && $this->hasroot){
            //         $this->language = $this->languages[0];
            //         $this->setSess('lang',$this->language);
            //     } else  {
            //         if(isset($this->sessiones['lang'])){
            //             $this->language = $this->sessiones['lang'];
            //         } else {
            //             $this->language = $this->languages[0];
            //             $this->setSess('lang',$this->language);
            //         }
            //     }
            //     (isset($this->uri[0]) ?  $this->route = $this->uri[0] : null );
            //     ($this->route == '' ? $this->route = '/': null );
            // }
            
            if(empty($this->defaultlang)){
                $this->defaultlang = $this->languages[0];
            }

            $this->setSess('defaultlang',$this->defaultlang);
            $this->setSess('route',$this->route);
            (!isset($this->request[0]) ? $this->request[0] = '' : null );
    }

    protected function  routeroot($mtd) {  //get post put delete ...
        if($this->isroot){
            //---- route ------------------- for root ---------------------
            $func = $mtd.ucfirst($this->route);
            // $this->dump('routeroot',$func,$this->route,method_exists($this,$this->route));
            if(in_array($func,$this->reservemethod)){
                array_shift($this->request);
                call_user_func_array([$this,$func],$this->request);
                return;
            } elseif(method_exists($this,$this->route)) {
                array_shift($this->request);
                call_user_func_array([$this,$this->route],$this->request);
                return;
            } elseif(method_exists($this,$func) ){
                array_shift($this->request);
                call_user_func_array([$this,$func],$this->request);
                return;
            } else {
                if($this->route == '/') {
                    if(!isset($this->request[0])){ $this->request[0] = ''; }
                    switch ($this->method) {
                        case 'POST':
                            $this->store($this->request[0]);
                            break;
                        case 'PUT':
                            $this->update($this->request[0]);
                            break;
                        case 'DELETE':
                            $this->destroy($this->request[0]);
                            break;
                        case 'GET':
                            $this->index();
                            break;
                        default:
                            break;
                    }
                } else if (array_key_exists($this->route, $this->routepages)) { 
                     $this->routepath[] = $this->request[0];
                     // $this->dump('x',$this->classname,$this->routepath);
                     array_shift($this->request);
                     $class =  $this->routepages[$this->route];
                     if(isset($this->server['DOCUMENT_ROOT'])){
                        require_once $this->server['DOCUMENT_ROOT'].'/'.$this->route.'.php';
                     } else {
                        require_once __DIR__.'/../'.$this->route.'.php';
                     }
                     if( isset($this->request[0]) ) {  $this->route = $this->request[0]; }
                     $tclass = new $class();
                     $tclass->make($this);
                     exit();
                } else {
                    $this->pagenotfound(1);
                }
            }
        } else {
            $this->pagenotfound(2);
            exit();
        }
    }    

    protected function pagenotfound($i) {
        if( $this->production){
            throw new Exception("Error! contact admin:thongchai@servit.co.th", 1);   
        } else {
            $this->setStatus(404);
            echo '-------- 404 Page or Method or class '.$this->route.' method:'.$this->method.' Not Found. '.$i.'-----------';
        }
    }

    /**
    * @param  int  $errno 
    * @param  Exception   $err   
    * @param  integer $code  = 0;
    * @param  string  $format json/xml
    * @param  code =  api response code 
    *  0 => 400 Unknown Error
    *  1 => 200 Success
    *  2 => 403 HTTPS Required
    *  3 => 401 Authentication Required
    *  4 => 401 Authentication Failed
    *  5 => 404 Invalid Request
    *  6 => 400 Invalid Response Format
    * @return json/xml
    * $this->rest_error(-1,$e,'json',0,__FILE__); //or
    */
    public function rest_error($errno,$err,$format='json',$code=0,$module=null){ 
        $o = new stdClass();
        if($err instanceof Langexception ){
            $o->errMsg       = $err->getMessage();
            $o->errCode      = $err->getCode();
            $this->response['errno'] = $err->getCode();
        } else if($err instanceof Exception ){
            $o->errMsg       = $err->getMessage();
            $o->errCode      = $err->getCode();
            $o->Trace        = $err->getTrace();
            $o->TraceString  = $err->getTraceAsString ();
            $this->response['errno'] = $err->getCode();
            //-------- chk multi language -------------------------------
                $o->errMsg = $this->langmsg($err->getMessage(),$module,[],$code,2);
            //-------- chk multi language -------------------------------
        } else  {
            $this->response['errno'] = $errno;
            $o->errMsg = $err;
            $o->code = $code;
        }
        $this->response['code'] = $code;
        $this->response['status'] = $this->api_response_code[$this->response['code'] ]['HTTP Response'];
        $this->response['data'] = $o;
        $this->deliver_response($format, $this->response);
        return;
    }

    protected function routes() {
        
        $path = [];
        foreach( $this->fullroute as $val ) {
            if($val=='routes'){
                break;
            } else {
                $path[] = $val;
            }
        }

        if($this->hasroot){
            $spath =  '/'.join('/',$path).'/';
        }   else  {
            $spath  = $this->server['SCRIPT_NAME'].'/';
        }
        if(!$this->production){
            $html = '<table width="600" border="1">
            <tr>
                <th align="center" width ="20">No</th>
                <th align="center"width ="80">Type</th>
                <th align="left" width="250">&nbsp;&nbsp;Path</th>
                <th align="left" width="250">&nbsp;&nbsp;Method</th>
            </tr><tbody>';

            $i = 1;
            foreach ($this->methodget as $method) {
                $method = (object) $method;
                $html .=  '<tr><td align="center">'.$i.'</td><td align="center">GET</td><td align="left"><a href="'.$spath.$method->path.'">&nbsp;&nbsp;/'.$method->path.'</a></td><td align="left">&nbsp;&nbsp;'.$method->method.'</td></tr>';
                $i++;
            }
            foreach ($this->methodput as $method) {
                $method = (object) $method;
                $html .=  '<tr><td align="center">'.$i.'</td><td align="center">PUT</td><td align="left">&nbsp;&nbsp;/'.$method->path.'</td><td align="left">&nbsp;&nbsp;'.$method->method.'</td></tr>';
                $i++;
            }
            foreach ($this->methodpost as $method) {
                $method = (object) $method;
                $html .=  '<tr><td align="center">'.$i.'</td><td align="center">POST</td><td align="left">&nbsp;&nbsp;/'.$method->path.'</td><td align="left">&nbsp;&nbsp;'.$method->method.'</td></tr>';
                $i++;
            }
            foreach ($this->methoddelete as $method) {
                $method = (object) $method;
                $html .=  '<tr><td align="center">'.$i.'</td><td align="center">DELETE</td><td align="left">&nbsp;&nbsp;/'.$method->path.'</td><td align="left">&nbsp;&nbsp;'.$method->method.'</td></tr>';
                $i++;
            }
            $html .= '</tbody></table>';
            echo $html;
        }
    }

    private  function deliver_response($format=null, $api_response){
        if($this->isajax){
            header('HTTP/1.1 '.$api_response['status'].' '.$this->http_response_code[ $api_response['status'] ]);
            if( strcasecmp($format,'json') == 0 ){
                header('Content-Type: application/json; charset=utf-8');
                $json_response = json_encode($api_response,JSON_UNESCAPED_UNICODE);
                ( ($this->isdebug && php_sapi_name() =='cli-server' ) ? error_log($json_response) : null );
                echo $json_response;
            } elseif( strcasecmp($format,'xml') == 0 ){
                header('Content-Type: application/xml; charset=utf-8');
                $xmlarr = ["response"=>$api_response];
                $xml =  $this->xml_encode($xmlarr);
                (($this->isdebug && php_sapi_name() =='cli-server' ) ? error_log($xml) : null );
                echo $xml;
            } else {
               header('Content-Type: application/json; charset=utf-8');
               // header('Content-Type: text/html; charset=utf-8');
               if(!$this->production){
                // $this->dump($api_response);
                echo $json_response;
               } 
            }
        } else {
        		// $this->dump('deliver',$format,$api_response);
        	// echo 'test';
        	   header('HTTP/1.1 '.$api_response['status'].' '.$this->http_response_code[ $api_response['status'] ]);
               header('Content-Type: application/json; charset=utf-8');
               // $json_response = json_encode($api_response);
               $json_response = json_encode($api_response,JSON_UNESCAPED_UNICODE);
               echo $json_response;
        }
        exit;
    }

    protected function  response($data,$format=null) {
        $this->response['code'] = 1;
        $this->response['status'] = $this->api_response_code[ $this->response['code'] ]['HTTP Response'];
        $this->response['data'] = $data;

        if($this->isdebug){
            if($format && $format != 'xml') {
                ($this->isajax ? $format='json' : $format = null );
            }
            $this->format = $format;
        } 
        
        if($format) {
            // $this->dump('c',$format,$this->response);
            $this->deliver_response($format, $this->response);
        } else {
            if($this->isajax){
                $this->format = 'json';
                $this->deliver_response($this->format, $this->response);
            } else {
                $this->format = 'json';
                $this->deliver_response($this->format, $this->response);
            }
        }
    }


    private  function  preser_function() {
        $class_methods = get_class_methods(get_class($this));
        foreach ($class_methods as $method) {
            if ( in_array($method,$this->reservemethod) ){
                $this->rest_error(-9,$method . ' is reserve function.');
                exit();
            } else {
                if( preg_split('@(?=[A-Z])@', $method)[0] == 'get' ){
                    $this->methodget[] = [ 'method'=>$method,  'path' => strtolower(explode('get',$method,2)[1])  ];
                }elseif( preg_split('@(?=[A-Z])@', $method)[0] == 'put' ){
                    $this->methodput[] = ['method'=>$method , 'path'=> strtolower(explode('put',$method,2)[1])  ];
                }elseif( preg_split('@(?=[A-Z])@', $method)[0] == 'post' ){
                    $this->methodpost[] = ['method'=>$method,'path'=>strtolower(explode('post',$method,2)[1])  ];
                }elseif( preg_split('@(?=[A-Z])@', $method)[0] == 'delete' ){
                    if($method != 'delete'){
                        $this->methoddelete[] = ['method'=>$method,'path'=>strtolower(explode('delete',$method,2)[1])  ];
                    }
                }
            }
        }

        $this->methodget[]    = ['method'=>'index','path'=>''];    //index()                /get---- getIndex

        if($this->usedb){
            $this->methodget[]    = ['method'=>'show','path'=>'show'];   // show($id)     /show/get----  getShow
            $this->methodget[]    = ['method'=>'create','path'=>'create']; //   create()      /create/get----   getcreate
            $this->methodget[]    = ['method'=>'edit','path'=>'edit'];    //edit($id)           /edit/get---- getEdit
            $this->methodget[]    = ['method'=>'all','path'=>'all'];   
            $this->methodget[]    = ['method'=>'lists','path'=>'lists'];  
            $this->methodget[]    = ['method'=>'searchs','path'=>'searchs'];  
            $this->methodget[]    = ['method'=>'search_criteria','path'=>'search_criteria']; //    store()                 /post--  postStore
            $this->methodput[]    = ['method'=>'update','path'=>'']; //    update($id)        /put--  putUpdate
            $this->methodpost[]   = ['method'=>'store','path'=>'/']; //    store()                 /post--  postStore
            $this->methodpost[]   = ['method'=>'search_criteria','path'=>'search_criteria']; //    store()                 /post--  postStore
            $this->methoddelete[] = ['method'=>'destroy','path'=>'']; //    destroy($id)      /delete--  deleteDestroy
        }

        $this->methodget[]    = ['method'=>'routes','path'=>'routes'];    //edit($id)           /edit/get---- getEdit
    }


    public function getVer(){
        if(!$this->production){
            echo  'Restful Server: ',$this->version,"\n<br>";
        }
    }


    public function rest_options($argv=null) {
        $this->rest_error(-1,'please extends method rest_options()','json',0); //or
    }

    public function getServerinfo(){
            if($this->isajax){
                $o = new stdClass();
                $o->this = $this;
                $o->chk = 'serverinfo';
                $o->isajax = $this->isajax;
                $this->response($o,'json');
            } else {
                echo ( !$this->production ?  $this->dump($this) : null );
            }
    }


    private function vardumpstyle(){
       if(!isset($this->vardump)){
           echo '<style>pre.sf-dump{display:block;padding:5px;background-color:#18171B;color:#FF8400;line-height:1.2em;font:12px Menlo,Monaco,Consolas,monospace;word-wrap:break-word;white-space:pre-wrap;position:relative;z-index:100000}pre.sf-dump span{display:inline}pre.sf-dump .sf-dump-compact{display:none}pre.sf-dump abbr{text-decoration:none;border:none;cursor:help}pre.sf-dump a{text-decoration:none;cursor:pointer;border:0;outline:0}pre.sf-dump .sf-dump-num{font-weight:700;color:#1299DA}pre.sf-dump .sf-dump-const{font-weight:700}pre.sf-dump .sf-dump-str{font-weight:700;color:#56DB3A}pre.sf-dump .sf-dump-note{color:#1299DA}pre.sf-dump .sf-dump-ref{color:#A0A0A0}pre.sf-dump .sf-dump-private,pre.sf-dump .sf-dump-protected,pre.sf-dump .sf-dump-public{color:#FFF}pre.sf-dump .sf-dump-meta{color:#B729D9}pre.sf-dump .sf-dump-key{color:#56DB3A}pre.sf-dump .sf-dump-index{color:#1299DA}</style>';
            $this->vardump = 1;
       }
    }

    private function  _init() {
        ( $this->isdebug ? $this->starttime() : null );
        $this->preser_function();
        if($this->useJs) {
            $this->javascript = new Javascript();     // $this->javascript->put(['foo'=>'bar']);
        }
        // Set default HTTP response of 'ok'
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->qrypath = ( filter_input(INPUT_SERVER, 'PATH_INFO') ? filter_input(INPUT_SERVER, 'PATH_INFO') : filter_input(INPUT_SERVER, 'REQUEST_URI'));
        $this->request = filter_input(INPUT_SERVER, 'PATH_INFO');
        $this->request =  rtrim($this->request,"\/");
        $this->request = explode("/", substr(@$this->request, 1));
        ( is_array($_FILES) ? $this->files = $_FILES : null );
        $uri = filter_input(INPUT_SERVER,'REQUEST_URI');
        ($uri ? $this->uri = explode("/", substr(@$uri, 1)) : $this->uri = [] );

        $this->qrystr = filter_input(INPUT_SERVER, 'QUERY_STRING');
        $php = filter_input(INPUT_SERVER, 'SCRIPT_NAME');
        $this->php = explode("/", substr(@$php, 1))[0];
        // parse_str($qrystr, $this->qrystr);
        $this->input = (object)   json_decode(file_get_contents("php://input"));
        $this->inputarr =  json_decode(file_get_contents("php://input"),TRUE);
        if($this->inputarr == null ) $this->inputarr = [];
        $this->posts = $_POST;
        $this->reqs = $_REQUEST;
        $this->cusheader = headers_list();
        if(!function_exists('apache_request_headers')){
            $arh = array();
            $rx_http = '/\AHTTP_/';
            foreach($_SERVER as $key => $val) {
                if( preg_match($rx_http, $key) ) {
                    $arh_key = preg_replace($rx_http, '', $key);
                    $rx_matches = array();
                    // do some nasty string manipulations to restore the original letter case
                    // this should work in most cases
                    $rx_matches = explode('_', $arh_key);
                    if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                        foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                        $arh_key = implode('-', $rx_matches);
                    }
                    $arh[$arh_key] = $val;
                }
            }
            $this->appcheheader = $arh;   
        } else {
            $this->appcheheader = apache_request_headers();
        }
        $this->format = filter_input(INPUT_GET, 'format', FILTER_SANITIZE_SPECIAL_CHARS);
        // ($this->format ? :$this->format='json');
        $this->func  = filter_input(INPUT_GET, 'method', FILTER_SANITIZE_SPECIAL_CHARS);
        // ( $this->func ? null : $func = 'hello');
        // echo 'this class = ',get_class($this),"\n";
        if($this->isdebug && $this->usedb){
            Capsule::enableQuerylog();
        }
    }

    private function  _info() {
           echo  'Restful Server '.$this->version,"\n<br>";
           echo  '--------------------------------',"\n<br>";
           echo  '  class   YourService extends RestfulServer  {',"\n<br>";
           echo  '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;---- you function ----',"\n<br>";
           echo  '  } ',"\n<br>";
           echo  ' $app = new YourService();',"\n<br>";
           echo  ' $app->run()',"\n<br>";
           echo  '--------------------------------',"\n<br>";
    } 

    public function run() {
        if( get_class($this) == 'RestfulServer' ) { 
                $this->_info();
        } else  {
            $this->init_route();
            switch ($this->method) {
                case 'GET':
                    if($this->useSocket) {
                        $this->setSocketjs();
                    }
                    $this->getGet();
                break;
                case 'PUT':
                    if($this->isroot){
                        $this->routeroot('put');
                        exit();
                    } else {
                        foreach ($this->methodput as $put) {
                            $put =(object) $put;
                            if(strtolower($put->path) == strtolower($this->request[0])){
                                array_shift($this->request);
                                call_user_func_array([$this,$put->method],$this->request);
                                return;
                            }
                        }
                        if($this->usedb){
                            if($this->request[0]){
                                  call_user_func_array([$this,'update'],$this->request);  // call this->update();
                                  return;
                           } 
                       }
                       $this->rest_error(-1,'Error: '.join('/',$this->fullroute).' PUT method not found.','');
                    }
                break;
                case 'POST':
                    if($this->isroot){
                        $this->routeroot('post');
                        exit();
                    } else {
                        foreach ($this->methodpost as $post) {
                            $post =(object) $post;
                            if(strtolower($post->path) == strtolower($this->request[0])){
                                array_shift($this->request);
                                call_user_func_array([$this,$post->method],$this->request);
                                return;
                            }
                       }
                       if($this->usedb){
                            if($this->request[0]){
                                  call_user_func_array([$this,'store'],$this->request);  // call this->update();
                                  return;
                           } 
                       }
                       $this->rest_error(-1,'Error: '.join('/',$this->fullroute).' POST method not found.','');
                    }
                break;
                case 'DELETE':
                    if($this->isroot){
                        $this->routeroot('delete');
                        exit();
                    } else {
                        foreach ($this->methoddelete as $delete) {
                            $delete =(object) $delete;
                            if(strtolower($delete->path) == strtolower($this->request[0])){
                                array_shift($this->request);
                                call_user_func_array([$this,$delete->method], $this->request );
                                return;
                            }
                        }
                        if($this->usedb){
                            if($this->request[0]){
                                $this->destroy($this->request[0]);
                                return;
                            } 
                        }
                        $this->rest_error(-1,'Error: '.$this->request[0].' DELETE method not found.','');
                    }
                break;
                case 'OPTIONS':
                case 'PATCH':
                    call_user_func_array([$this,'rest_options'], $this->request );
                    break;
                default:
                    $err = 'error no method';
                    $this->rest_error(-1,$err);
                break;
            }
        }
    }

    protected function dump(){
        $args =[];
        $numargs = func_num_args();
        for($i=0;$i<$numargs;$i++){
            $args[] = func_get_arg($i);
        }
        if(!$this->isajax){
            $this->vardumpstyle();
            dump($args);
        }
    }

    protected function redirect($url=null) {
      if($url){
        header("location: $url");
      }
    }

    protected function refresh($url) {
        if($url){
            header("refresh: 2; url=$url");
        }
    }

    protected function ajaxdata(){
        if(!$this->production){
                $o = new stdClass();
                $o->usedb = $this->usedb;
                $o->useSocket = $this->useSocket;
                $o->useJs = $this->useJs;
                $o->useJwt = $this->useJwt;
                $o->isdebug = $this->isdebug;
                $o->start_time = $this->start_time;
                $o->version = $this->version;
                $o->host = $this->host;
                $o->file = $this->file;
                $o->server = $this->server;
                $o->method = $this->method;
                $o->request = $this->request;
                $o->qrystr = $this->qrystr;
                $o->input = $this->input;
                $o->inputarr = $this->inputarr;
                $o->qrypath = $this->qrypath;
                $o->reqs = $this->reqs;
                $o->uri = $this->uri;
                $o->posts = $this->posts;
                $o->files = $this->files;
                $o->isajax = $this->isajax;
                $o->sessiones = $this->sessiones;
                $o->cookie = $this->cookie;
                $o->maxtime = $this->maxtime;
                $o->privateKey = $this->privateKey;
                $o->publicKey = $this->publicKey;
                $o->signer = $this->signer;
                $o->format = $this->format;
                $o->loginpath = $this->loginpath;
                $o->useslug = $this->useslug;
                $o->apikey = $this->apikey;
                $o->response = $this->response;
                $o->css = $this->css;
                $o->js = $this->js;
                $o->jslast = $this->jslast;
                $o->navbar = $this->navbar;
                $o->header = $this->header;
                $o->content = $this->content;
                $o->footer = $this->footer;
                $o->htmlhead = $this->htmlhead;
                $o->htmlfooter = $this->htmlfooter;
                $o->appopt = $this->appopt;
                $o->menu = $this->menu;
                $o->bladengine = $this->bladengine;
                $o->production = $this->production;
                $o->javascript = $this->javascript;
                $o->appcheheader = $this->appcheheader;
                $o->backendbase = $this->backendbase;
                $o->http_response_code = $this->http_response_code;
                $o->HTTPS_required = $this->HTTPS_required;
                $o->authentication_required = $this->authentication_required;
                $o->api_response_code = $this->api_response_code;
                $o->methodget = $this->methodget;
                $o->methodput = $this->methodput;
                $o->methodpost = $this->methodpost;
                $o->methoddelete = $this->methoddelete;
                $o->reservemethod = $this->reservemethod;
                $o->exptime = $this->exptime;
                $o->token = $this->token;
                $o->secretKey = $this->secretKey;
                $o->model = $this->model;
                $o->modelwhere = $this->modelwhere;
                $o->fills = $this->fills;
                $o->pk = $this->pk;
                $o->socket = $this->socket;
                $o->socketserver = $this->socketserver;
                $o->hasSocket = $this->hasSocket;
                $o->cusheader = $this->cusheader;
                $o->userlogin = $this->isUserlogin();
                $o->adminlogin = $this->isAdminlogin();
                return $o;            
        }
    }
    protected function islogin($role=false){
            if(!isset($_SESSION['admin_cms'])){
                throw new Exception("Please Login", 1);
            }

            if($role){
              
                if (!in_array($_SESSION['admin_cms']->user_role, $role)) {
                    throw new Exception("Permission Denied", 1);
                }
               
            }
    }
    protected function isAdminlogin(){
     try {
            $auth = false;
            switch ($this->authtype) {
                case 'session':
                    ( !empty($_SESSION['adminLogin'] ) ? $auth = true : null ); 
                    break;
                case 'jwt':
                    if($this->tokenverify()){
                        $this->dump($this->token->getClaim('username'));        
                        return $this->token->getClaim('username')->isadmin;
                    }
                    break;
                case 'cookie':
                    (!$_COOKIE['adminLogin'] ? $auth = true : null );
                    break;
                default:
                    break;
            }

            $this->isadsminlogin = $auth;
            if($auth){
                return true;
            } else {
                 throw new Exception("Please login admin before", 1);
            }
        } catch (Exception $e) {
            $this->rest_error(-1,$e,'json',0,__FILE__); //or
        }
    }

    protected function isUserlogin(){
     try {
            $auth = false;
            switch ($this->authtype) {
                case 'session':
                    ( !empty($_SESSION['client'] ) ? $auth = true : null ); 
                    break;
                case 'jwt':
                    if($this->tokenverify()){
                        return $this->token->getClaim('username')->islogin;
                    } 
                    break;
                case 'cookie':
                    ( !empty($_COOKIE['client'] ) ? $auth = true : null ); 
                    break;
                default:
                     $auth = false;
                    break;
            }

            $this->isuserlogin = $auth;
            if($auth){
                return true;
            } else {
                throw new Exception("Please login before", 1);
            }
        } catch (Exception $e) {
            $this->rest_error(-1,$e,'json',0,__FILE__); //or
        }
    }

    protected function obj2array($obj){
        return json_decode(json_encode($obj),true);
    }

    protected function setSess($key=null,$value=null) {
        if($key){
            $this->sessiones[$key] = $value;
            $_SESSION[$key] = $value;
        } 
    }

    protected function getsess($key=null) {
        if($key){
            if (array_key_exists($key, $this->sessiones)) {
                return $this->sessiones[$key];
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    protected function clearSess(){
        $this->sessiones = [];
        $_SESSION = [];
    }   

    protected function megrevar($obj) {
        $keys = ['usedb','useSocket','useJs','useJwt','isroot','isdebug','useslug','apikey','css','js','jslast','navbar','header','content','footer','htmlhead','ht[mlfooter','appopt','menu','production','javascript','backendbase','http_response_code','api_response_code','m[ethodget','methodput','methodpost','methoddelete','reservemethod','exptime','token','secretKey','model','modelwhere','fills','pk','php','classname','language'];
        foreach( $obj as $k => $v ){
            if(!in_array($k,$keys)){
                $this->$k = $v;
            }
        } 
    }

    protected function replactClassvar($obj) {
        foreach( $obj as $k => $v ){
                $this->$k = $v;
        } 
    }


    protected  function make($obj){
        $this->megrevar($obj);
        $this->isroot = false;
        $this->methodget = [];
        $this->methodput = [];
        $this->methodpost = [];
        $this->methoddelete = [];
        $this->preser_function();
        $this->run();
    }

    //-------------- Rest Server --------------------------------- start ---

    protected function xml_encode($mixed, $domElement=null, $DOMDocument=null) {  //@todo add type hint for $domElement and $DOMDocument
        if (is_null($DOMDocument)) {
            $DOMDocument =new DOMDocument;
            $DOMDocument->formatOutput = true;
            $this->xml_encode($mixed, $DOMDocument, $DOMDocument);
            return $DOMDocument->saveXML();
        } else {
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
            }else {
                $domElement->appendChild($DOMDocument->createTextNode($mixed));
            }
        }
    }

    public function setStatus($code)   {
        if (function_exists('http_response_code')) {
            http_response_code($code);
        } else {
            $protocol = $_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
            $code .= ' ' . $this->http_response_code[strval($code)];
            header("$protocol $code");
        }
    }

    public function refreshCache()     {
        $this->map = array();
        $this->cached = false;
    }

    public function unauthorized($ask = false)     {
        if ($ask) {
            header("WWW-Authenticate: Basic realm=\"$this->realm\"");
        }
        throw new RestException(401, "You are not authorized to access this resource.");
    }

    public function handle()   {
        $this->url = $this->getpath();
        $this->method = $this->getmethod();
        $this->format = $this->getformat();

        if ($this->method == 'PUT' || $this->method == 'POST' || $this->method == 'PATCH') {
            $this->data = $this->getdata();
        }

        list($obj, $method, $params, $this->params, $noAuth) = $this->findUrl();

        if ($obj) {
            if (is_string($obj)) {
                if (class_exists($obj)) {
                    $obj = new $obj();
                } else {
                    throw new Exception("Class $obj does not exist");
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

                // dump($this);
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
    public function setRootPath($path)    {
        $this->rootPath = '/'.trim($path, '/').'/';
    }
    public function setJsonAssoc($value)     {
        $this->jsonAssoc = ($value === true);
    }

    public function addClass($class, $basePath = '')  {

        $this->loadCache();

        if (!$this->cached) {
            if (is_string($class) && !class_exists($class)){
                throw new Exception('Invalid method or class');
            } elseif (!is_string($class) && !is_object($class)) {
                throw new Exception('Invalid method or class; must be a classname or object');
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

    public function addErrorClass($class)   {
        $this->errorClasses[] = $class;
    }

    public function handleError($statusCode, $errorMessage = null)  {
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
            $errorMessage = $this->http_response_code[$statusCode];
        }

        $this->setStatus($statusCode);
        $this->sendData(array('error' => array('code' => $statusCode, 'message' => $errorMessage)));
    }

    protected function loadCache()    {
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

    protected function findUrl()    {
        $urls = $this->map[$this->method];
        if (!$urls) return null;

        foreach ($urls as $url => $call) {
            $args = $call[2];

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

    protected function generateMap($class, $basePath)    {
        if (is_object($class)) {
            $reflection = new ReflectionObject($class);
        } elseif (class_exists($class)) {
            $reflection = new ReflectionClass($class);
        }

        $methods = $reflection->getmethods(ReflectionMethod::IS_PUBLIC);    //@todo $reflection might not be instantiated

        foreach ($methods as $method) {
            $doc = $method->getDocComment();
            $noAuth = strpos($doc, '@noAuth') !== false;
            if (preg_match_all('/@url[ \t]+(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)[ \t]+\/?(\S*)/s', $doc, $matches, PREG_SET_ORDER)) {

                $params = $method->getParameters();

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
            }
        }
    }

    private function getpath()    {
        $path = preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);
        // remove root from path
        if ($this->root) $path = preg_replace('/^' . preg_quote($this->root, '/') . '/', '', $path);
        // remove trailing format definition, like /controller/action.json -> /controller/action
        $path = preg_replace('/\.(\w+)$/i', '', $path);
        // remove root path from path, like /root/path/api -> /api
        if ($this->rootPath) $path = str_replace($this->rootPath, '', $path);
        return $path;
    }

    private function getmethod()    {
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

    private function getformat()    {
        $format = RestFormat::PLAIN;
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

    private function getdata()    {
        $data = file_get_contents('php://input');
        $data = json_decode($data, $this->jsonAssoc);

        return $data;
    }

    public function sendData($data)    {
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: 0");
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
            if ($this->mode == 'debug') {
                $options = JSON_PRETTY_PRINT;
            }
            $options = $options | JSON_UNESCAPED_UNICODE;
            echo json_encode($data, $options);
        }
    }

    //-------------- Rest Server --------------------------------- end  ---
      protected function mc_encrypt($encrypt){
          $encrypt = serialize($encrypt);
          $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM);
          $key = pack('H*', $this->encryption_key);
          $mac = hash_hmac('sha256', $encrypt, substr(bin2hex($key), -32));
          $passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $encrypt.$mac, MCRYPT_MODE_CBC, $iv);
          $encoded = base64_encode($passcrypt).'|'.base64_encode($iv);
          return $encoded;
      }
      // Decrypt Function
      protected function mc_decrypt($decrypt){
          $decrypt = explode('|', $decrypt.'|');
          $decoded = base64_decode($decrypt[0]);
          $iv = base64_decode($decrypt[1]);
          if(strlen($iv)!==mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC)){ return false; }
          $key = pack('H*', $this->encryption_key);
          $decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $decoded, MCRYPT_MODE_CBC, $iv));
          $mac = substr($decrypted, -64);
          $decrypted = substr($decrypted, 0, -64);
          $calcmac = hash_hmac('sha256', $decrypted, substr(bin2hex($key), -32));
          if($calcmac!==$mac){ return false; }
          $decrypted = unserialize($decrypted);
          return $decrypted;
      }

      protected function setlang(){
        // $this->dump($this->languages,$this->language);
        if(count($this->languages)>1){
            return $this->language->name.'/';
        } else {
            return '';
        }
      }


    protected function renderString($string, array $parameters)
    {
        $replacer = function ($match) use ($parameters)
        {
            return isset($parameters[$match[1]]) ? $parameters[$match[1]] : $match[0];
        };

        return preg_replace_callback('/{{\s*(.+?)\s*}}/', $replacer, $string);
    }


    protected function renderStringx($template =null, $data = [])
    {
        if($template) {
               foreach($data as $key => $value)
               {
                 $template = str_replace('{'.$key.'}', $value, $template);
               }
               return $template;
        } else {
            return ;
        }
    }

    protected function langmsg($message=null,$module='',$data=[],$code='-9999',$type=1) {
        $chk = 1;
        if($message) {
            foreach ($this->langmessages as $msg) {
                if($msg->val == $message ) {
                    if($msg->{$this->language->ref}){
                        $chk = 0;
                        return $this->renderString($msg->{$this->language->ref},$data);
                    } else {
                        $chk = 0;
                        return $this->renderString($msg->val,$data);
                    }
                }
            }
            if($chk) {
                $this->langmesageadd($message,$code,$type,$module);
                return $this->renderString($message,$data);
            } 
            return;
        } else { return; }
    }

    protected function throwmsg($message,$data=[],$module='',$code=0) {
        error_log('throwmsg');
        $chk = 1;
        if($message) {
            // $this->dump($message);
            foreach ($this->langmessages as $msg) {
                // $this->dump($msg->val,$message,$msg->val == $message,$this->language->ref);
                if($msg->val == $message ) {
                    if($msg->{$this->language->ref}){
                        $chk = 0;
                        throw new Langexception($this->renderString($msg->{$this->language->ref},$data),$msg->code);
                    } else {
                        $chk = 0;
                        throw new Langexception($this->renderString($msg->val,$data),$msg->code);
                    }
                }
            }
            if($chk) {
                $type = 2;
                $this->langmesageadd($message,$code,$type,$module);
                throw new Langexception($message, 0);
            } else {
                return;
            }
        } else {
            return;
        }
    }

    private function langmesageadd($message='',$code='-9999',$type=1,$module='',$desc=null) {
        if($message) {
            $lan = Langmessage::where('val',$message)->first();
            if(!$lan){
                $l = new Langmessage();
                $l->code = $code;
                $l->val = $message;
                $l->type = $type;
                $l->module = basename($module);
                $l->lang1 = $message;
                $l->lang2 = $message;
                $l->lang3 = $message;
                $l->lang4 = $message;
                if($desc) {
                    $l->desc = $desc;
                }
                $l->save();
            }
        }
    }


}

class RestFormat {
    const PLAIN = 'text/plain';
    const HTML  = 'text/html';
    const JSON  = 'application/json';
    const XML   = 'application/xml';

    /** @var array */
    static public $formats = array(
        'plain' => RestFormat::PLAIN,
        'txt'   => RestFormat::PLAIN,
        'html'  => RestFormat::HTML,
        'json'  => RestFormat::JSON,
        'xml'   => RestFormat::XML,
    );
}

class RestException extends Exception {
    public function __construct($code, $message = null)
    {
        parent::__construct($message, $code);
    }
}

class Restdata {
    protected $values;
    
    public function __construct($obj=null){
        if($obj) {
        	$this->values = $obj;
        } else {
        	$this->values = new stdClass();
        }

    }

    public function __get($prop) {
        return (isset($this->values->$prop) ? $this->values->$prop : null  );
    }

    public function __set( $prop, $value ) {
             $this->values->$prop = $value;
    }



}

class Optdata {
    protected $values;
    public function __construct($data){
        $this->values = $data;
    }

    public function __get($prop) {

        foreach ($this->values as $value) {
            if($value['pop_key'] == $prop){
                return $value['pop_value'];
            }
        }
        return null;
    }


    public function getdata($prop) {
        foreach ($this->values as $value) {
            if($value['pop_key'] == $prop){
                return $value;
            }
        }
        return null;
    }



} //----- end of class 


class Langexception extends Exception {


}

//============================= Server run ======================================================= 
// 1---------- extends this Server --------
// $app = new  RestfulServer();
// $app->run();
// 
// 2--------- include this Server and Add Class Controller ---------
// $server = new RestfulServer('debug');
// $server->addClass('TestController','/');
// $server->addClass('TestController','/aaa');
// $server->handle();
//===================================================================================== end clasee


