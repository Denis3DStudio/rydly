<?php 

    class Request {

        #region Public Methods

            public static function Get($only_request = true) {

                // Init response
                $response = new stdClass();
                $response->Request = null;
                $response->Method = strtoupper($_SERVER['REQUEST_METHOD']);
                $response->Valid = true;

                // Init request
                $request = null;

                // Get the request
                switch ($response->Method) {
                    case 'GET':
                        // Check from $_GET
                        $request = $_GET;

                        // Check from php://input
                        if($request == null) {
                            $request = file_get_contents("php://input");

                            // Set empty
                            if(Base_Functions::IsNullOrEmpty($request))
                                $request = array();
                        }
                        break;
                    case 'POST':
                    case 'PUT':
                    case 'DELETE':
                        // Get request
                        $request = file_get_contents("php://input");

                        // Check if found
                        if(Base_Functions::IsNullOrEmpty($request) && isset($_SERVER["CONTENT_TYPE"]) && Base_Functions::HasSubstring($_SERVER["CONTENT_TYPE"], "form-data"))
                            $request = $_POST;

                        break;
                    default:
                        $response->Valid = false;
                        break;
                }

                // Check if request is json
                if(Base_Functions::IsJson($request))
                    $request = json_decode($request);

                // Check request values to find file call array
                if(!Base_Functions::IsNullOrEmpty($request))
                    foreach ($request as $key => $value) {
                        
                        // Check if value is a string and has FILE_CALL_ARRAY as substring
                        if(is_string($value) && Base_Functions::HasSubstring($value, "FILE_CALL_ARRAY"))
                            $request[$key] = explode("FILE_CALL_SEPARATOR_ARRAY", str_replace("FILE_CALL_ARRAY", "", $value));
                    
                    }

                // Set response request object
                $response->Request = $request;

                return $only_request ? $request : $response;
            }

            public static function VerifyValidationHeader($request) {

                // Check if not string
                if(!is_string($request))
                    $request = json_encode($request);

                // Check payload for datatable
                if(!Base_Functions::IsNullOrEmpty(self::GetHeader("Datatable-Ajax-Call")))
                    return json_decode($request);

                // Get validation header origin
                $validation_token_origin = self::GetHeader("Validation-Header-Origin");

                // Check if is null and set SERVER
                if(Base_Functions::IsNullOrEmpty($validation_token_origin))
                    $validation_token_origin = "SERVER";

                // Get validation header
                $validation_token = self::GetHeader("Payload-Validation-Header");

                // Check if in API Whitelist
                if(self::CheckAPIWhitelist($validation_token_origin == "MOBILE-APP"))
                    return json_decode($request);

                // Check if validation token is empty
                if (Base_Functions::IsNullOrEmpty($validation_token)) {

                    if(defined("IS_DEBUG") && IS_DEBUG) {

                        $response = new stdClass();
                        $response->IsBaseRequestResponse = true;
                        $response->Error = "Validation Token is empty";

                        return $response;

                    }

                    return false;
                }

                // Format request
                $check_request = json_encode(self::FormatRequestForValidationHeader(json_decode($request)), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

                // Get client unix or current timestamp
                $unix = !Base_Functions::IsNullOrEmpty(self::GetHeader("Payload-Validation-Header-Unix")) ? intval(self::GetHeader("Payload-Validation-Header-Unix")) : time();

                // Init keys and decodes
                $keys = [];
                $decodes = [];

                // Try to decode with 5 sec
                for ($i=0; $i < 5; $i++) { 
                        
                    // Build key
                    $key = $unix + $i;

                    // Check if is mobile app
                    if(strtoupper($validation_token_origin) == "MOBILE-APP") {
                     
                        // Create the encoding with sha1 + seconds
                        $encode = sha1($check_request . $key);

                        // Check if valid
                        if($encode == $validation_token) {
                            array_push($keys, $key);

                            break;
                        }

                    }

                    // SERVER or EXTERNAL
                    else {

                        // Decode with JS
                        if(strtoupper($validation_token_origin) == "EXTERNAL")
                            $decode = Base_Encryption::DecryptJs($validation_token, $key);

                        // Decode with PHP
                        else if(strtoupper($validation_token_origin) == "SERVER")
                            $decode = Base_Encryption::Decrypt($validation_token, $key);

                        // Check if decoded
                        if(!Base_Functions::IsNullOrEmpty($decode) && !is_bool($decode) && $decode != $validation_token && (strtoupper($validation_token_origin) == "EXTERNAL" || (strtoupper($validation_token_origin) == "SERVER" && Base_Encryption::Encrypt($decode, $key) == $validation_token))) {
                            array_push($keys, $key);
                            array_push($decodes, $decode);
                        }
                    }

                    $key = null;
                }

                if(count($keys) == 0) {

                    if(defined("IS_DEBUG") && IS_DEBUG) {

                        $response = new stdClass();
                        $response->IsBaseRequestResponse = true;
                        $response->Error = "No decode done for the validation token provided";
                        $response->CheckRequest = $check_request;

                        return $response;

                    }
                    
                    return false;
                }

                // Check if is not mobile app
                if(strtoupper($validation_token_origin) != "MOBILE-APP") {

                    // Init valid key index
                    $valid_key_index = 0;
                        
                    // Get valid decodes
                    foreach ($decodes as $index => $decode) {
                        // Check if is a valid sha256
                        if(preg_match("/^([a-f0-9]{64})$/", $decode) == true)
                            $valid_key_index = $index;
                    }

                    // Set key with last value
                    $key = $keys[$valid_key_index];

                    // Get last decode
                    $decode = $decodes[$valid_key_index];

                    // Check if not decoded or wrong request
                    if(!Base_Functions::IsNullOrEmpty($validation_token) && (Base_Functions::IsNullOrEmpty($key) || $decode != hash('sha256', $check_request))) {

                        if(defined("IS_DEBUG") && IS_DEBUG) {
    
                            $response = new stdClass();
                            $response->IsBaseRequestResponse = true;
                            $response->Error = "Comparison failed";
                            $response->CheckRequest = $check_request;
    
                            return $response;
    
                        }

                        return false;
                    }

                }

                // Decode
                return json_decode($request);
            }

            public static function GetHeader($header_name) {
                $header_value = null;

                // Get request headers
                $requestHeaders = apache_request_headers();
                
                // Format the array
                foreach ($requestHeaders as $key => $value) {
                    
                    // Explode key by -
                    $key = explode("-", $key);

                    // Set each to lowercase and then ucfirst
                    $key = array_map(function($item) {
                        return ucfirst(strtolower($item));
                    }, $key);

                    // Set the key and value
                    $requestHeaders[implode("-", $key)] = $value;

                }
                
                // Check if found
                if(isset($requestHeaders[$header_name]))
                    $header_value = trim($requestHeaders[$header_name]);

                // Check if is empty
                if(Base_Functions::IsNullOrEmpty($header_value) && isset($_SERVER[$header_name]))
                    $header_value = trim($_SERVER[$header_name]);

                // Check if is empty
                return Base_Functions::IsNullOrEmpty($header_value) ? null : $header_value;
            }

            public static function FormatRequestForValidationHeader($request) {
                
                foreach ($request as $key => $value) {
                    $format = $value;
                    
                    // Check if is a number
                    if(is_numeric($value)) {

                        // Check length (after 16 digits, the number is rounded automatically in JS)
                        if(strlen($value) >= 16)
                            $format = strval($value);
                        else
                            $format = strval(intval($value)) == $value ? intval($value) : floatval($value);

                    }

                    // Check if is a string
                    else if (is_string($value)) {

                        if(Base_Functions::IsNullOrEmpty($value))
                            $format = null;

                        else
                            $format = trim(preg_replace('/[\x00-\x09\x0B-\x1F\x7F-\xFF]/', '', $format));
                    }

                    // Check if is an object
                    else if(is_object($value) || is_array($value))
                        $format = self::formatRequestForValidationHeader($value);

                    // Set
                    if(is_object($request))
                        $request->{$key} = $format;
                        
                    else if(is_array($request))
                        $request[$key] = $format;
                }

                return $request;

            }

            public static function CheckAPIWhitelist($mobile = false, $is_request_checker = false) {

                // Filename
                $filename = $mobile ? "mobile-validation-header-whitelist.json" : "validation-header-whitelist.json";

                // Check if is request checker
                if($is_request_checker) $filename = "request-check-whitelist.json";

                // Init file path
                $file = $_SERVER["DOCUMENT_ROOT"] . API_FOLDER . "/routes/$filename";

                // Check if exists the whitelist file
                if(file_exists($file)) {
                    
                    // Check content
                    $whitelist = json_decode(file_get_contents($file));

                    // Check if is an array
                    if(is_array($whitelist)) {

                        // Get current url without query string
                        $url = strtoupper(strtok($_SERVER["REQUEST_URI"], '?'));
                        
                        // Create to replace
                        $to_replace = isset($_REQUEST["b"]) ? [strtoupper("/" . $_REQUEST["b"] . "-api"), strtoupper("/" . $_REQUEST["b"] . "-bff"), strtoupper("/" . $_REQUEST["b"])] : "";

                        // Replace
                        $url = str_replace($to_replace, "", $url);

                        // Array filter on whitelist
                        $whitelist = array_filter($whitelist, function($item) use ($url, $to_replace) {
                            return ltrim(rtrim(str_replace($to_replace, "", strtoupper($item)), "/"), "/") == ltrim(rtrim($url, "/"), "/");
                        });

                        // Check if is in whitelist
                        if(count($whitelist) > 0)
                            return true;
                    }

                }

                return false;

            }
        
        #endregion
        
    }