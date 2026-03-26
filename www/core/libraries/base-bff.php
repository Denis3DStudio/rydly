<?php

    class Base_BFF {

        #region Properties

            private $__API = [];
            private $__API_requests = [];
            private $__current_url = null;
            private $__current_endpoint = null;

            private $Response;
            private $Request;

            private $__jwt = "";
            private $IsDTServerSide = false;

        #endregion

        #region Constructors-Destructors

            public function __construct($api, $requests) {

                $this->__API = $api;
                $this->__API_requests = $requests;

                // Init response
                $this->Response = new Response();

                // Init request
                $this->Request = new stdClass();

                $this->IsDTServerSide = false;

            }
            public function __destruct() {}

        #endregion

        #region Main Methods

            public function Fire() {

                // Set session cookie
                Base_Auth::sessionCookieName();

                $this->request();

                $this->call();

            }

        #endregion

        #region Request Methods

            private function request() {

                // Check Url
                $this->checkUrl();

                // Check request
                $this->checkRequest();

                // Check if exists the Dt-Server-Side header
                $this->checkDTServerSide();

                // Get request and check header
                $this->getRequest();

                // Check request's fields
                $this->checkRequestFields();

            }

            private function checkUrl() {
                // Build url
                $this->__current_url = $this->getCurrentUrl();

                foreach ($this->__API->{$_SERVER['REQUEST_METHOD']} as $controller) {
                    foreach ($controller as $endpoint) {

                        if(rtrim($endpoint->Url, "/") == rtrim($this->__current_url, "/")) {
                            $this->__current_endpoint = $endpoint;
                            break;
                        }

                    }

                    if(!Base_Functions::IsNullOrEmpty($this->__current_endpoint))
                        break;
                }

                // Overwrite current url
                $this->__current_url = str_replace("bff", "api", $this->__current_url);

                // Check auth
                $this->auth();

                // Check if found and not valid
                if(!Base_Functions::IsNullOrEmpty($this->__current_endpoint) && $this->__current_endpoint->Valid == false) {

                    // Check if exists the custom method
                    $this->checkCustomMethods(null, true);

                    $this->__current_endpoint = null;
                }

                // Not found
                if(Base_Functions::IsNullOrEmpty($this->__current_endpoint)) {
                    $this->Response->Not_Found(null, "BFF - Endpoint not found");
                    $this->Response->IsGeneric = true;
                    $this->Response->FireResponse($this->Response);
                }
            }

            private function checkDTServerSide() {

                $this->IsDTServerSide = !Base_Functions::IsNullOrEmpty(Request::GetHeader("Dt-Server-Side"));
            }

            private function getRequest() {

                // Get request
                $request = Request::Get(false);

                // Check if is a preflight request - response is the CORS
                if($request->Method == "OPTIONS") {
                    $this->Response->Success();
                    $this->Response->FireResponse($this->Response);
                }

                // Check if valid
                if(!$request->Valid) {
                    $this->Response->Method_Not_Allowed(null, "BFF - Method not allowed");
                    $this->Response->IsGeneric = true;
                    $this->Response->FireResponse($this->Response);
                }

                // Overwrite
                $request = $request->Request;

                $payload = null;

                // Check request
                if(!Base_Functions::IsNullOrEmpty($request)) {

                    $payload = (object)$request;

                    // Check validation header if not datatable server side
                    if(!$this->IsDTServerSide) {
                        $payload = Request::VerifyValidationHeader($request);

                        // Check if not decoded or wrong request
                        if($payload === false || (defined("IS_DEBUG") && IS_DEBUG && is_object($payload) && property_exists($payload, "IsBaseRequestResponse"))) {
                            $this->Response->Bad_Request($payload === false ? null : $payload, "BFF - Client > Server Request Changed or Expired");
                            $this->Response->IsGeneric = true;
                            $this->Response->FireResponse($this->Response);
                        }
                    }
                }

                // Set request payload
                $this->Request = $payload;
            }

            private function checkRequest() {
                $method = strtoupper($_SERVER['REQUEST_METHOD']);

                // Build url
                $url = Base_Encryption::Encrypt(rtrim($this->getCurrentUrl(), "/"));

                if(!property_exists($this->__API_requests, $method) || !property_exists($this->__API_requests->{$method}, $url)) {
                    $this->Response->Not_Found(null, "BFF - Request url not found");
                    $this->Response->IsGeneric = true;
                    $this->Response->FireResponse($this->Response);
                }
            }

            private function checkRequestFields() {
                $method = strtoupper($_SERVER['REQUEST_METHOD']);

                // Build url
                $url = Base_Encryption::Encrypt(rtrim($this->getCurrentUrl(), "/"));

                // Get request object
                $obj = $this->__API_requests->{$method}->{$url};

                // Check if has path
                if(!property_exists($obj, "Path"))
                    return;

                $obj->Path = strtolower($obj->Path);

                // Check request file
                if(!property_exists($obj, "Path") || !file_exists($_SERVER["DOCUMENT_ROOT"] . $obj->Path)) {
                    $this->Response->Not_Found(null, "BFF - Request model not found");
                    $this->Response->IsGeneric = true;
                    $this->Response->FireResponse($this->Response);
                }

                // Get request
                $request = json_decode(file_get_contents($_SERVER["DOCUMENT_ROOT"] . $obj->Path));

                // Check if class exists
                if(Base_Functions::IsNullOrEmpty($request) || !property_exists($request, $obj->Class)) {
                    $this->Response->Not_Found(null, "BFF - Empty request model");
                    $this->Response->IsGeneric = true;
                    $this->Response->FireResponse($this->Response);
                }

                // Get keys
                $payload_keys = array_keys((array)$this->Request);
                $model_keys = array_keys((array)$request->{$obj->Class});

                // Check if is a datatable server side call
                if($this->IsDTServerSide)
                    $model_keys = array_merge($model_keys, $this->getDTServerRequest());

                // Get not required fields
                $diff = array_diff($payload_keys, $model_keys);

                // Compare
                if(count($diff)) {
                    $this->Response->Bad_Request(null, "BFF - Fields `" . implode(" - ", $diff) . "` not required");
                    $this->Response->IsGeneric = true;
                    $this->Response->FireResponse($this->Response);
                }

                // Get mandatory
                $mandatory = array_keys(array_filter((array)$request->{$obj->Class}, function($item) {
                    return $item === true || (is_object($item) && property_exists($item, "Mandatory") && $item->Mandatory == true);
                }));

                // There are mandatory
                if(count($mandatory) > 0) {

                    foreach ($mandatory as $field) {

                        // Check if not exists or is null
                        if(Base_Functions::IsNullOrEmpty($this->Request) || !property_exists($this->Request, $field) || Base_Functions::IsNullOrEmpty($this->Request->{$field}))
                            $this->Response->Bad_Request(null, "BFF - Mandatory field `$field` not set");

                    }

                    // Check if something wrong
                    if(!$this->Response->Success) {
                        $this->Response->IsGeneric = true;
                        $this->Response->FireResponse($this->Response);
                    }

                }
            }

            private function getDTServerRequest() {

                // Set the path of the server side json
                $json_server_side_path = "/datatable/requests/get.json";
                $json_server_side_class = "ServerSide";

                // Get object
                $classes_to_merge = $this->getParamsClasses($json_server_side_path);

                // Return only keys
                return array_keys((array)$classes_to_merge->{$json_server_side_class});

            }

        #endregion

        #region Auth Methods

            private function auth() {

                // Build url
                $url = Base_Encryption::Encrypt(rtrim($this->getCurrentUrl(), "/"));

                // Set method
                $method = strtoupper($_SERVER['REQUEST_METHOD']);

                // Check if exists
                if(!property_exists($this->__API_requests, $method) || !property_exists($this->__API_requests->{$method}, $url)) {
                    $this->Response->Not_Found(null, "BFF - Request url not found");
                    $this->Response->IsGeneric = true;
                    $this->Response->FireResponse($this->Response);
                }

                // Get request object
                $obj = $this->__API_requests->{$method}->{$url};

                // Get session
                $session = Base_Auth::getSession(true);

                // Check if require auth
                if($obj->Auth && Base_Functions::IsNullOrEmpty($session)) {
                    $this->Response->Unauthorized(null, "BFF - Session not found");
                    $this->Response->IsGeneric = true;
                    $this->Response->FireResponse($this->Response);
                }

                // Create JWT
                if(!Base_Functions::IsNullOrEmpty($session))
                    $this->__jwt = (new Base_JWT())->generateJWT($session);
            }

        #endregion

        #region CURL Methods

            private function call() {

                // Add $_FILES
                $this->buildFilesRequest();

                // Build headers
                $headers = $this->buildHeaders();

                // Check request dt server side
                $headers = $this->checkRequestDt($headers);

                // Init curl
                $curl = new Base_Curl();

                // Call API
                $curl
                    ->setType(strtoupper($_SERVER['REQUEST_METHOD']))
                    ->setUrl(URL_WWW . $this->__current_url);

                // Check request type is GET
                if(strtoupper($_SERVER['REQUEST_METHOD']) == "GET")
                    $curl->setEncodeRequest(false);

                // Check headers
                if(!Base_Functions::IsNullOrEmpty($headers))
                    $curl->setHeaders($headers);

                // Check request object
                if(!Base_Functions::IsNullOrEmpty($this->Request))
                    $curl->setObject($this->Request);

                // Check JWT
                if(!Base_Functions::IsNullOrEmpty($this->__jwt))
                    $curl->setBearerToken($this->__jwt);

                // Check if has $_FILES
                if(count($_FILES) > 0) {
                    $curl->setContentType("multipart/form-data");
                    $curl->setEncodeRequest(false);
                }

                // Fire
                $response = $curl->call();

                // Check response
                $response = $this->checkResponse($response);

                // Set response custom props
                $this->Response->SetCustomProperties(clone $response);

                // Set response
                $this->Response->{"Code" . $curl->__response_code}($response);

                // Check if there is a custom method for the current API
                $this->checkCustomMethods($response);

                // Show response
                $this->Response->FireResponse($this->Response);
                exit;
            }

            private function buildHeaders() {
                $headers = [];

                // Get payload validation
                $header = Request::GetHeader("Payload-Validation-Header");
                if(!Base_Functions::IsNullOrEmpty($header)) array_push($headers, "Payload-Validation-Header: $header");

                // Get payload validation origin
                $header = Request::GetHeader("Validation-Header-Origin");
                if(!Base_Functions::IsNullOrEmpty($header)) array_push($headers, "Validation-Header-Origin: $header");

                // Get payload validation unix
                $header = Request::GetHeader("Payload-Validation-Header-Unix");
                if(!Base_Functions::IsNullOrEmpty($header)) array_push($headers, "Payload-Validation-Header-Unix: $header");

                // Get datatable ajax call
                $header = Request::GetHeader("Datatable-Ajax-Call");
                if(!Base_Functions::IsNullOrEmpty($header)) array_push($headers, "Datatable-Ajax-Call: $header");

                // Get content length
                $header = Request::GetHeader("Content-Length");
                if(!Base_Functions::IsNullOrEmpty($header) && count($_FILES) == 0) array_push($headers, "Content-Length: $header");

                // Get dt server side
                $header = Request::GetHeader("Dt-Server-Side");
                if(!Base_Functions::IsNullOrEmpty($header)) array_push($headers, "Dt-Server-Side: $header");

                return $headers;
            }
            private function buildFilesRequest() {

                // Check if has files
                if(count($_FILES) > 0) {

                    if(Base_Functions::IsNullOrEmpty($this->Request))
                        $this->Request = new stdClass();

                    // Format arrays in request
                    foreach ($this->Request as $key => $value) {

                        // Check if value is an array and cast to FILE_CALL_ARRAY
                        if(is_array($value))
                            $this->Request->{$key} = "FILE_CALL_ARRAY" . implode("FILE_CALL_SEPARATOR_ARRAY", $value);

                    }

                    // Insert FILES
                    foreach ($_FILES as $index => $file) {

                        // Count files
                        $count = count($file["name"]);

                        for ($i=0; $i < $count; $i++)
                            $this->Request->{$index . "[$i]"} = curl_file_create($file["tmp_name"][$i], $file["type"][$i], $file["name"][$i]);

                    }

                    // Cast to array
                    $this->Request = (array)$this->Request;

                }

            }
            private function checkRequestDt($headers) {

                // Check datatable params
                if(Request::GetHeader("Dt-Server-Side")) {

                    // Get querystring
                    $querystring = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

                    // Parse
                    parse_str($querystring, $queries);

                    // Merge
                    if(Base_Functions::IsNullOrEmpty($this->Request))
                        $this->Request = (object)$queries;

                    else
                        $this->Request = Base_Functions::mergeObjects($this->Request, (object)$queries);

                    // Create key and value
                    $unix = time() + 3;

                    $value = Base_Encryption::Encrypt(hash('sha256', json_encode(Request::FormatRequestForValidationHeader($this->Request), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)), $unix);

                    $found = false;

                    // Regerate Payload-Validation-Header
                    foreach ($headers as $index => $header) {
                        if(Base_Functions::HasSubstring($header, "Payload-Validation-Header")) {
                            $headers[$index] = "Payload-Validation-Header: $value";
                            $found = true;
                            break;
                        }
                    }

                    // Check if found
                    if(!$found) {
                        array_push($headers, "Payload-Validation-Header: $value");
                        array_push($headers, "Validation-Header-Origin: SERVER");
                    }
                }

                return $headers;
            }
            private function checkResponse($response) {

                // Check if response is null
                if(Base_Functions::IsNullOrEmpty($response) || !is_object($response)) {

                    // Check if is string
                    $value = is_string($response) ? $response : null;

                    $response = new stdClass();
                    $response->Response = $value;
                }

                // Check if from datatable server side
                if(!Base_Functions::IsNullOrEmpty(Request::GetHeader("Dt-Server-Side"))) {

                    // Set properties
                    if(!property_exists($response, "recordsTotal"))
                        $response->recordsTotal = 0;

                    if(!property_exists($response, "recordsFiltered"))
                        $response->recordsFiltered = 0;

                }

                return $response;
            }

        #endregion

        #region Custom Methods

            private function checkCustomMethods($response = null, $fire = false) {
                // Check if exists method
                if(Base_Functions::IsNullOrEmpty($this->__current_endpoint->Method))
                    return;

                // Explode the current_url
                $active_api = array_filter(explode("/", $this->__current_url));

                // Remove the first element
                $active = array_shift($active_api);

                // Replace -api
                $active = str_replace("-api", "", $active);

                // Build controller name
                $controller_name = explode("/", str_replace("/$active-api/", "", $this->__current_url))[0];

                // Active
                $active = ltrim(ACTIVE_PATH, "/");

                // Build path
                $path = $_SERVER["DOCUMENT_ROOT"] . "/$active/controllers/" . strtolower($controller_name) . ".php";

                // Check if exists
                if(!file_exists($path))
                    return;

                // Include file
                include_once($path);

                // Build namespace
                $namespace = "Controller\\$controller_name\\Methods";

                // Init
                $custom = new $namespace();

                // Get method name
                $method = $this->__current_endpoint->Method;

                // Check if method exists
                if(!method_exists($custom, $method))
                    return;

                // Init properties
                $this->initMethodBaseProperties($custom);

                // Set response
                $custom->Response = $this->Response;

                // Check that is not null
                if (!Base_Functions::IsNullOrEmpty($response) && !Base_Functions::IsNullOrEmpty($response->Response))
                    $custom->{$method}($response->Response);

                else {

                    // Check method required params
                    $reflection = new ReflectionMethod($custom, $method);
                    $number = $reflection->getNumberOfRequiredParameters();

                    // Build params
                    $params = array_fill(0, $number, null);

                    // Call method
                    call_user_func_array(array($custom, $method), $params);
                }

                // Overwrite response
                $this->Response = $custom->Response;

                // Fire response
                if($fire)
                    $this->Response->FireResponse($this->Response);
            }

        #endregion

        #region Private Methods

            private function getCurrentUrl() {

                return strtok($_SERVER["REQUEST_URI"], "?");

            }
            private function getParamsClasses($path) {

                // Full path
                if(Base_Functions::HasSubstring($path, "/"))
                    $path = $_SERVER["DOCUMENT_ROOT"] . "/api/endpoints/backend" . str_replace(".json", "", $path) . ".json";

                // Lower
                $path = strtolower($path);

                // Check if file exists
                if(!file_exists($path)) {
                    $this->Response->Internal_Server_Error(null, "BFF - Request model not found `$path`");
                    $this->Response->IsGeneric = true;
                    $this->Response->FireResponse($this->Response);
                }

                // Get request classes
                $classes = json_decode(file_get_contents(str_replace("//", "/", $path)));

                return $classes;
            }

            private function initMethodBaseProperties($instance) {

                // Build define name
                $session_name = Session_Name_Current();

                // Check if already exists
                $session = Session_Get($session_name);

                // Set logged
                $instance->Logged = Base_Functions::IsNullOrEmpty($session) ? null : $session->getAttributes();

                // Set response
                $instance->Response = $this->Response;

                // Utils
                $instance->__linq = new Base_LINQHelper();
                $instance->__opHelper = new Base_OperationsHelper();
                $instance->__opHelper->__setCreator(!Base_Functions::IsNullOrEmpty($instance->Logged) ? $instance->Logged->IdAccount : null);

            }

        #endregion

    }