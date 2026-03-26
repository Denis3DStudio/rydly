<?php

    class Base_Api {

        #region Properties
            private $Url;
            public $Response;
            private $CurrentRoute;
            
            private $Request;

            private $Method;
            private $Logged;

            private $Routes;
            private $RoutesPath;

            private $EndpointsPath;

            private $IsDTServerSide;

            private $InternalRequest;
            private $InternalRequestSet;
        #endregion

        #region Constructors-Destructors

            public function __construct($base) {

                // Set as not internal request
                $this->InternalRequest = false;
                $this->InternalRequestSet = false;

                // Check uri from GET or get from SERVER
                if(isset($_GET["REQUEST_URI"])) {

                    // Check if has value
                    if(!Base_Functions::IsNullOrEmpty($_GET["REQUEST_URI"]))
                        $uri = $_GET["REQUEST_URI"];

                    unset($_GET["REQUEST_URI"]);

                    $this->InternalRequest = true;

                }
                else
                    $uri = $_SERVER['REQUEST_URI'];

                // Set config
                new Base_Config();

                // Remove query string
                $uri = strtok($uri, '?');

                // Init
                $this->Url = array_filter(explode('/', str_replace("$base-api", "", htmlentities($uri))));

                $this->Response = new Response();
                $this->CurrentRoute = new stdClass();

                $this->Request = new stdClass();
                
                $this->Method = null;
                $this->Logged = null;
                
                $this->Routes = new stdClass();
                $this->RoutesPath = $_SERVER["DOCUMENT_ROOT"] . API_FOLDER . "/routes/$base-routes.json";

                $this->EndpointsPath = $_SERVER["DOCUMENT_ROOT"] . API_FOLDER . "/endpoints/$base/";

                $this->IsDTServerSide = false;

                // Get request url
                $this->GetRequestUrl();
            }
            public function __destruct() {}
            
        #endregion

        #region Main Methods

            private function GetRequestUrl() {

                // Get url
                $this->getUrl();
                
                // Init routes
                $this->initRoutes();

                // Set api name
                $this->Request->Api = $this->Url[0];

                // Set API request method
                $this->getApiRequestMethod();

                // Set API controller
                $this->getController();

                // Check if exists the Dt-Server-Side header
                $this->checkDTServerSide();

                // Set API
                $this->getApi();
        
                // Check API Method
                $this->Method();
            }

            private function checkDTServerSide() {

                $this->IsDTServerSide = !Base_Functions::IsNullOrEmpty(Request::GetHeader("Dt-Server-Side"));
            }

            private function Method() {
                
                // Check if is exist the dt server side header (set Datatable as Api)
                $api = $this->IsDTServerSide ? "Datatable" : $this->Request->Api;

                // Build methods file path
                $path = $this->EndpointsPath . strtolower($api) . "/methods.php";

                // Check if methods file exists
                if(!file_exists($path)) {
                    $this->Response->Not_Found(null, "API - API file methods not found");
                    $this->Response->IsGeneric = true;
                    $this->Response();
                }

                // Include methods file
                include_once($path);

                // Check if is exist the dt server side header (replace the Api name with 'Datable' in the namespace)
                $namespace = $this->IsDTServerSide ? str_replace($this->Request->Api, "Datatable", $this->Request->Namespace) : $this->Request->Namespace;

                // Get classname with namespace
                $className = $namespace . "\Methods";

                // Check if class exists
                if(!class_exists($className)) {
                    $this->Response->Not_Found(null, "API - API class methods `$className` not found");
                    $this->Response->IsGeneric = true;
                    $this->Response();
                }
            
                // Merge this instance with the methods class
                $this->Method = new $className();

                // Check if is exist the dt server side header (Set the name of the method of the datatable class)
                $method = $this->IsDTServerSide ? "serverSide" : $this->CurrentRoute->Method;

                // Check if route method exists
                if(!method_exists($this->Method, $method)) {
                    $this->Response->Not_Implemented(null, "API - No API method `" . $this->CurrentRoute->Method . "` implemented");
                    $this->Response->IsGeneric = true;
                    $this->Response();
                }

                // Check authentication
                $this->checkAuth();

                // Check request
                $this->checkRequest();

                // Init properties
                $this->initMethodBaseProperties();

                try {
                    
                    // Call method
                    if($this->Response->Success)
                        $this->invokeMethod();
                    else
                        $this->Method->Success = false;
                    
                    $this->Response($this->Method);

                } catch (\Throwable $th) {

                    // Something went wrong
                    $this->Response->Internal_Server_Error(null, "API - " . $th->getFile() . ", " . $th->getLine() . " " . $th->getMessage());
                    $this->Response->IsGeneric = true;
                    $this->Response();
                
                }
            }

        #endregion

        #region Route Method

            private function getUrl() {

                // Format 
                $this->Url = array_values(array_filter(array_map(function ($item, $index) {
        
                    // If index is 0 there can be the version (backward compatibility)
                    if($index == 0 && strtoupper($item) == "V1")
                        return null;
        
                    return trim($item);
        
                }, $this->Url, array_keys($this->Url))));
            
                // Check url validity
                if(count($this->Url) == 0) {
                    $this->Response->Bad_Request(null, "API - Invalid Request Url");
                    $this->Response->IsGeneric = true;
                    $this->Response();
                }

                // Set full url
                $this->Request->Url = implode("/", $this->Url);

            }

            private function getApiRequestMethod() {

                // Get request
                $this->getRequest();

                // Set method
                $method = $this->Request->Method;

                // Set api name
                $api = $this->Request->Api;

                // Check if method exists
                if(property_exists($this->Routes, $method)) {

                    // Check if api exists
                    if(property_exists($this->Routes->{$method}, $api)) {

                        // Set current route
                        $this->CurrentRoute = $this->Routes->{$method};

                        return;
                    }

                }

                // Show error
                $this->Response->Not_Implemented(null, "API - No API implemented in `" . $this->Request->Api . "` with method `" . $this->Request->Method . "`");
                $this->Response->IsGeneric = true;
                $this->Response();
            }

            private function getController() {

                // Check if exists in routes
                if(!property_exists($this->CurrentRoute, $this->Request->Api)) {
                    $this->Response->Not_Found(null, "API - No API `" . $this->Request->Api . "` found");
                    $this->Response->IsGeneric = true;
                    $this->Response();
                }

                // Get endpoints folders
                $endpoints = array_map(function ($item) { return strtolower(basename($item)); }, glob($this->EndpointsPath . "*"));

                // Check if exists in endpoints
                if(!in_array(strtolower($this->Request->Api), $endpoints)) {
                    $this->Response->Not_Implemented(null, "API - No API `" . $this->Request->Api . "` implemented");
                    $this->Response->IsGeneric = true;
                    $this->Response();
                }

                // Unset controller from url
                array_shift($this->Url);

                // If no route, push empty value
                if(count($this->Url) == 0)
                    array_push($this->Url, "");

                // Set current route
                $this->CurrentRoute = $this->CurrentRoute->{$this->Request->Api};

                // Get active path without first slash
                $active = ltrim(ACTIVE_PATH, "/");

                // UCFirst
                $active = ucfirst(strtolower($active));

                // Set request namespace
                $this->Request->Namespace = $active . "\\" . $this->Request->Api;

            }

            private function getApi() {

                // Set api route
                $this->Request->Route = implode("/", $this->Url);

                // Check if exists in routes
                if(!property_exists($this->CurrentRoute, $this->Request->Route)) {
                    $this->Response->Not_Found(null, "API - No API route `" . $this->Request->Route . "` found");
                    $this->Response->IsGeneric = true;
                    $this->Response();
                }
                
                // Set current route
                $this->CurrentRoute = $this->CurrentRoute->{$this->Request->Route};

                // Check method property
                if(!property_exists($this->CurrentRoute, "Method"))
                    $this->CurrentRoute->Method = strtolower($this->Request->Method);
            }

        #endregion

        #region Method Methods

            private function checkAuth() {

                // Get logged account
                $is_logged = $this->getLoggedAccount();

                // Check if needed
                if(!property_exists($this->CurrentRoute, "Auth") || $this->CurrentRoute->Auth == false)
                    return;

                // Get logged account
                if(!$is_logged) {
                    $this->Response->Unauthorized(null, "API - Not logged");
                    $this->Response->IsGeneric = true;
                    $this->Response();
                }
            }
            
            private function getParamsClasses($path) {

                // Full path
                if(Base_Functions::HasSubstring($path, "/"))
                    $path = $this->EndpointsPath . str_replace(".json", "", $path) . ".json";

                // Partial path
                else
                    $path = $this->EndpointsPath . $this->Request->Api . "/requests/" . str_replace(".json", "", $path) . ".json";

                // Lower
                $path = strtolower($path);

                // Check if file exists
                if(!file_exists($path)) {
                    $this->Response->Internal_Server_Error(null, "API - Request model not found `$path`");
                    $this->Response->IsGeneric = true;
                    $this->Response();
                }

                // Get request classes
                $classes = json_decode(file_get_contents(str_replace("//", "/", $path)));

                return $classes;
            }

            private function checkRequest() {

                // Check if is API whitelist
                if(Request::CheckAPIWhitelist(false, true)) {

                    // Set request but only payload
                    $this->Method->Request = $this->Request->Payload;
                    
                    return;
                }

                // Set the path of the server side json
                $json_server_side_path = "/datatable/requests/get.json";
                $json_server_side_class = "ServerSide";

                $serverSideRequest = false;

                // Check if has request
                if(!property_exists($this->CurrentRoute, "Request") || Base_Functions::IsNullOrEmpty($this->CurrentRoute->Request)) {

                    // Check if is exist the dt server side header (Set a default request obj)
                    if ($this->IsDTServerSide) {
                        
                        // Create the object for the request
                        $this->CurrentRoute->Request = new stdClass();
                        // Set the path of the json
                        $this->CurrentRoute->Request->Path = $json_server_side_path;
                        // Set the name of the class in the json file
                        $this->CurrentRoute->Request->Class = $json_server_side_class;

                        // Set the serverSideRequest as already insert in the request
                        $serverSideRequest = true;
                    }
                    else {

                        // Check payload
                        $keys = array_keys((array)$this->Request->Payload);
    
                        // If has properties, error and not internal
                        if(count($keys) > 0 && !$this->InternalRequest) {
                            $this->Response->Bad_Request(null, "API - Not required fields found: " . implode(", ", $keys));
                            $this->Response->IsGeneric = true;
                            $this->Response();
                        }
    
                        return;
                    }
                }

                // Get request classes
                $classes = $this->getParamsClasses($this->CurrentRoute->Request->Path);

                // Check if class exists
                if(Base_Functions::IsNullOrEmpty($classes) || !property_exists($classes, $this->CurrentRoute->Request->Class)) {
                    $this->Response->Internal_Server_Error(null, "API - Request class not found");
                    $this->Response->IsGeneric = true;
                    $this->Response();
                }

                // Get class params
                $params = $classes->{$this->CurrentRoute->Request->Class};

                /// Check if is exist the dt server side header (merge the serverside request with the real request)
                if($this->IsDTServerSide && !$serverSideRequest) {

                    $classes_to_merge = $this->getParamsClasses($json_server_side_path);
                    $params_to_merge = $classes_to_merge->{$json_server_side_class};

                    $params = Base_Functions::mergeObjects($params, $params_to_merge);
                }

                $this->checkFields($params);

                // Set request but only payload
                $this->Method->Request = $this->Request->Payload;

            }

            private function initMethodBaseProperties($instance = null) {
                if(Base_Functions::IsNullOrEmpty($instance)) $instance = $this->Method;

                // Set logged
                $instance->Logged = $this->Logged;

                // Utils
                $instance->__linq = new Base_LINQHelper();
                $instance->__opHelper = new Base_OperationsHelper();
                $instance->__opHelper->__setCreator(!Base_Functions::IsNullOrEmpty($instance->Logged) ? $instance->Logged->IdAccount : null);

            }

            private function invokeMethod() {
                $args = array();
                $mandatory = 0;

                $method = ($this->IsDTServerSide) ? "serverSide" : $this->CurrentRoute->Method;

                // Get method params
                $reflection = new ReflectionMethod($this->Method, $method);
                $params = $reflection->getParameters();                

                if ($this->IsDTServerSide)
                    $this->Request->Payload->ServerSideKey = Request::GetHeader("Dt-Server-Side");

                // Check name
                foreach ($params as $param) {

                    // Check if optional
                    if(!$param->isOptional())
                        $mandatory++;

                    // Get name
                    $name = $param->getName();

                    // Check if exists in payload
                    $arg = array_values(array_filter(array_filter(array_keys((array)$this->Request->Payload), function($key) use($name) { return strtoupper($key) == strtoupper($name); })));

                    // Check if found else set default value
                    array_push($args, count($arg) > 0 ?  $this->Request->Payload->{$arg[0]} : $param->getDefaultValue());

                }

                // Check if all mandatory found
                if(count($args) >= $mandatory)
                    call_user_func_array(array($this->Method, $method), $args);

                else
                    $this->Method->{$method}();

            }

        #endregion

        #region Request Methods

            private function checkFields($params) {

                // Check fields
                $this->checkRequiredFields($params);

                // Check mandatory
                $this->checkMandatoryFields($params);

                // Check optional
                $this->checkOptionalFields($params);

                // Check payload format
                $this->checkPayloadFieldsFormat($params);
            }

            private function checkRequiredFields($params) {

                // Get required keys
                $keys = array_keys((array)$params);

                // Get sent keys
                $sent = array_keys((array)$this->Request->Payload);

                // Get not required fields
                $diff = array_diff($sent, $keys);

                if(count($diff)) {
                    $this->Response->Bad_Request(null, "API - Fields `" . implode(" - ", $diff) . "` not required");
                    $this->Response->IsGeneric = true;
                }
            }

            private function checkMandatoryFields($params) {
                // Init mandatory
                $this->Request->Mandatory = array();

                // Get all mandatory
                foreach ($params as $key => $value) {
                    
                    // Check if true or is an object
                    if((is_bool($value) && $value == true) || (is_object($value) && property_exists($value, "Mandatory") && $value->Mandatory == true))
                        array_push($this->Request->Mandatory, $key);

                }

                // Check if found any
                if(count($this->Request->Mandatory) == 0)
                    return;

                // Check payload
                foreach ($this->Request->Mandatory as $field) {

                    // Check if not exists or is null
                    if($this->Request->Payload !== false && (Base_Functions::IsNullOrEmpty($this->Request->Payload) || !property_exists($this->Request->Payload, $field) || Base_Functions::IsNullOrEmpty($this->Request->Payload->{$field}))) {
                        $this->Response->Bad_Request(null, "API - Mandatory field `$field` not set");
                        $this->Response->IsGeneric = true;
                    }

                }

                // Check if something wrong
                if(!$this->Response->Success)
                    $this->Response();
            }

            private function checkOptionalFields($params) {

                foreach ($params as $key => $value) {
                    
                    // Check if not in mandatory, not set in payload or empty value
                    if($this->Request->Payload !== false && (!in_array($key, $this->Request->Mandatory) && (Base_Functions::IsNullOrEmpty($this->Request->Payload) || !property_exists($this->Request->Payload, $key) || $this->Request->Payload->{$key} == null || $this->Request->Payload->{$key} == "" ))) {

                        // Check if payload exists
                        if(Base_Functions::IsNullOrEmpty($this->Request->Payload))
                            $this->Request->Payload = new stdClass();

                        // Check if has Default property
                        if(is_object($value) && property_exists($value, "Default"))
                            $this->Request->Payload->{$key} = $value->Default;

                        // Set empty array
                        else if(is_object($value) && property_exists($value, "Array"))
                            $this->Request->Payload->{$key} = array();

                        // Set empty object
                        else if(is_object($value) && property_exists($value, "Object"))
                            $this->Request->Payload->{$key} = new stdClass();

                        // Set null
                        else
                            $this->Request->Payload->{$key} = null;

                    }

                }

            }

            private function checkPayloadFieldsFormat($params) {

                // Get array keys
                $arrays = array_filter((array)$params, function($item) { return is_object($item) && property_exists($item, "Array"); });

                // Get simple array keys
                $simple = array_filter($arrays, function($item) { return $item->Array == "*"; });

                // Format payload keys to simple
                if(Base_Functions::IsNullOrEmpty($this->Request->Payload))
                    $this->Request->Payload = new stdClass();
                
                foreach ($simple as $key => $value) {
                    
                    if(!Base_Functions::IsNullOrEmpty($this->Request->Payload) && property_exists($this->Request->Payload, $key))
                        $this->Request->Payload->{$key} = array_values($this->Request->Payload->{$key});

                    else
                        $this->Request->Payload->{$key} = array();

                }

                // Get class array keys
                $class = array_filter($arrays, function($item) { return is_object($item->Array) && in_array("Class", array_keys((array)$item->Array)) && in_array("Path", array_keys((array)$item->Array)); });

                // Format payload keys to class
                foreach ($class as $key => $value) {

                    // Check if payload is an array of objects
                    if (is_array($this->Request->Payload->{$key})) {
                        if(count($this->Request->Payload->{$key}) == 0)
                            continue;
                    }
                    else {
                        $this->Response->Bad_Request(null, "API - Property `$key` is not an array");
                        $this->Response->IsGeneric = true;
                        continue;
                    }

                    // Get request classes
                    $classes = $this->getParamsClasses($value->Array->Path);

                    // Check if class exists
                    if (!property_exists($classes, $value->Array->Class)) {
                        $this->Response->Internal_Server_Error(null, "API - Request class not found");
                        $this->Response->IsGeneric = true;
                        $this->Response();
                    }

                    foreach ($this->Request->Payload->{$key} as $array_key => $array_data) {

                        // Save the payload in a temp value
                        $temp_payload = $this->Request->Payload;

                        // Get class params
                        $params = $classes->{$value->Array->Class};

                        // Overwrite payload with temperary data to check
                        $this->Request->Payload = $array_data;

                        // Check if the array_data is correct
                        $this->checkFields($params);

                        // Update the temp var
                        $temp_payload->{$key}[$array_key] = $this->Request->Payload;

                        // Update the $this->Request->Payload with the temp_payload value
                        $this->Request->Payload = $temp_payload;
                    }
                }

                // Get object keys
                $objects = array_filter((array)$params, function($item) { return is_object($item) && property_exists($item, "Object"); });

                // Format the objects to the payload
                foreach ($objects as $key => $value) {

                    // Check if payload is an objects
                    if (is_object($this->Request->Payload->{$key})) {
                        if(count((array)$this->Request->Payload->{$key}) == 0)
                            continue;
                    }
                    else {
                        $this->Response->Bad_Request(null, "API - Property `$key` is not an object");
                        $this->Response->IsGeneric = true;
                        continue;
                    }

                    // Get request classes
                    $classes = $this->getParamsClasses($value->Object->Path);

                    // Check if class exists
                    if (!property_exists($classes, $value->Object->Class)) {
                        $this->Response->Internal_Server_Error(null, "API - Request class not found");
                        $this->Response->IsGeneric = true;
                        $this->Response();
                    }

                    // Save the payload in a temp value
                    $temp_payload = $this->Request->Payload;

                    // Get class params
                    $params = $classes->{$value->Object->Class};

                    // Overwrite payload with temperary data to check
                    $this->Request->Payload = $this->Request->Payload->{$key};

                    // Check if the array_data is correct
                    $this->checkFields($params);

                    // Update the temp var
                    $temp_payload->{$key} = $this->Request->Payload;

                    // Update the $this->Request->Payload with the temp_payload value
                    $this->Request->Payload = $temp_payload;
                }

                // Check if something wrong
                if(!$this->Response->Success)
                    $this->Response();
            }

        #endregion

        #region Response Methods

            private function Response($instance = null) {
                if(Base_Functions::IsNullOrEmpty($instance)) $instance = $this->Response;

                // Check if not internal
                if(!$this->InternalRequest)
                    $this->Response->FireResponse($instance);

                // Overwrite $this->Response with instance and add success
                else if(!$this->InternalRequestSet) {
                    $this->InternalRequestSet = true;
                    $this->Response = $instance;
                }
            }

        #endregion
        
        #region Private Methods

            private function initRoutes() {

                if(!file_exists($this->RoutesPath)) {
                    $this->Response->Not_Found(null, "API - File `" . $this->RoutesPath . "` not found");
                    $this->Response->IsGeneric = true;
                    $this->Response();
                }

                // Get routes
                $routes = Base_Functions::APIRoutesReorder(json_decode(file_get_contents($this->RoutesPath)));

                // Check routes
                if(Base_Functions::IsNullOrEmpty($routes)) {
                    $this->Response->Not_Found(null, "API - No routes found");
                    $this->Response->IsGeneric = true;
                    $this->Response();
                }

                // Set routes
                $this->Routes = $routes;

            }

            private function getRequest() {
                
                // Get request
                $request = Request::Get(false);

                // Check if is a preflight request - response is the CORS
                if($request->Method == "OPTIONS") {
                    $this->Response->Success();
                    $this->Response();
                }
                
                // Check if valid
                if(!$request->Valid) {
                    $this->Response->Method_Not_Allowed(null, "API - Invalid Request");
                    $this->Response->IsGeneric = true;
                    $this->Response();
                }

                // Set request method
                $this->Request->Method = $request->Method;

                // Overwrite
                $request = $request->Request;

                $payload = null;

                // Check request
                if(!Base_Functions::IsNullOrEmpty($request) && !$this->IsDTServerSide) {

                    $payload = Request::VerifyValidationHeader($request);

                    // Check if not decoded or wrong request
                    if($payload === false || (defined("IS_DEBUG") && IS_DEBUG && is_object($payload) && property_exists($payload, "IsBaseRequestResponse"))) {
                        $this->Response->Bad_Request($payload === false ? null : $payload, "API - Client > Server Request Changed or Expired");
                        $this->Response->IsGeneric = true;
                        $this->Response();
                    }
                }

                // Set request payload
                $this->Request->Payload = $payload;
            }

            private function getLoggedAccount() {

                $jwt_helper = new Base_JWT();

                // Check bearer token
                if($jwt_helper->verifyJWT()->Valid === true) {

                    // Get payload
                    $payload = $jwt_helper->getPayload();

                    if(!Base_Functions::IsNullOrEmpty($payload)) {

                        // Get payload backup token
                        $token = $payload->Token;

                        // Remove payload backup token
                        unset($payload->Token);
                        if(property_exists($payload, "__sessionName")) unset($payload->__sessionName);

                        // Crypt the current payload
                        $crypted = Base_Encryption::Encrypt(json_encode($payload));

                        // Check that the data in the backup are equal than the payload data
                        if ($crypted == $token) {

                            // Get the roles token from header
                            $available_roles_token = Request::GetHeader("Roles-Token");

                            $valid = true;

                            // Check if header exists
                            if(!Base_Functions::IsNullOrEmpty($available_roles_token)) {

                                // Decrypt the token
                                $available_roles = json_decode(Base_Encryption::DecryptJs($available_roles_token, "x30Yni3RbC247t1u3bVOFYSGxTR888cGCb2H3CLpEQh5sZwnuN"));

                                // Check if is valid
                                $valid = Base_Functions::IsNullOrEmpty($available_roles) || (property_exists($payload, "IdRole") && in_array($payload->IdRole, $available_roles));

                            }

                            // Check if valid
                            if ($valid) {

                                $this->Logged = $payload;

                                // Check if the user has the language property
                                if (property_exists($this->Logged, "IdLanguage"))
                                    Translations::$IdLanguage = $this->Logged->IdLanguage;

                                Base_Logs::Logged($payload->IdAccount);

                                return true;
                            }
                        }
                    }

                }

                return false;

            }

        #endregion

    }