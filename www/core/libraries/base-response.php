<?php 

    class Response {

        #region Properties
        
            public $Code;
            public $IsGeneric;
            public $Response;
            public $Message;
            public $Success;
            public $ServerSideTotalCount;
            public $ServerSideFilteredCount;
            public $CustomProperties;
            public $Errors;

        #endregion

        #region Constructors-Destructors

            public function __construct() { 

                // Init
                $this->Code = 200;
                $this->Response = new stdClass();
                $this->Message = "";
                $this->Success = true;
                $this->Errors = array();
                $this->CustomProperties = false;
                $this->ServerSideFilteredCount = false;
                $this->ServerSideFilteredCount = false;
            }
            public function __destruct() { 
            }

        #endregion

        #region Response Methods

            public function Success($response = null, $message = "") {
                $this->Code = 200;
                $this->IsGeneric = Base_Functions::IsNullOrEmpty($message);

                // Set message
                $message = Base_Functions::IsNullOrEmpty($message) ? "" : $message;

                return $this->SetResponse($response, $message);
            }
            public function Not_Found($response = null, $message = null) {
                $this->Code = 404;
                $this->IsGeneric = Base_Functions::IsNullOrEmpty($message);

                // Set message
                $message = Base_Functions::IsNullOrEmpty($message) ? "" : $message;
                
                return $this->SetResponse($response, $message);
            }
            public function Bad_Request($response = null, $message = null) {
                $this->Code = 400;
                $this->IsGeneric = Base_Functions::IsNullOrEmpty($message);

                // Set message
                $message = Base_Functions::IsNullOrEmpty($message) ? "" : $message;
                
                return $this->SetResponse($response, $message);
            }
            public function Unauthorized($response = null, $message = null) {
                $this->Code = 401;
                $this->IsGeneric = Base_Functions::IsNullOrEmpty($message);

                // Set message
                $message = Base_Functions::IsNullOrEmpty($message) ? "" : $message;
                
                return $this->SetResponse($response, $message);
            }
            public function Method_Not_Allowed($response = null, $message = null) {
                $this->Code = 405;
                $this->IsGeneric = Base_Functions::IsNullOrEmpty($message);

                // Set message
                $message = Base_Functions::IsNullOrEmpty($message) ? "" : $message;
                
                return $this->SetResponse($response, $message);
            }
            public function Internal_Server_Error($response = null, $message = null) {
                $this->Code = 500;
                $this->IsGeneric = Base_Functions::IsNullOrEmpty($message);

                // Set message
                $message = Base_Functions::IsNullOrEmpty($message) ? "" : $message;
                
                return $this->SetResponse($response, $message);
            }
            public function Not_Implemented($response = null, $message = null) {
                $this->Code = 501;
                $this->IsGeneric = Base_Functions::IsNullOrEmpty($message);

                // Set message
                $message = Base_Functions::IsNullOrEmpty($message) ? "" : $message;
                
                return $this->SetResponse($response, $message);
            }

            public function Free_Response($instance) {

                // Check if has Code, Response and Message
                if(property_exists($instance, "Code") && property_exists($instance, "Response") && property_exists($instance, "Message")) {
                    $this->Code = $instance->Code;

                    return $this->SetResponse($instance->Response, $instance->Message);
                }

                return $this->Bad_Request();
            }
        
        #endregion
        
        #region Code Methods

            public function Code200($response) {
                $this->Success($response->Response, $response->Message);
            }
            public function Code404($response) {
                $this->Not_Found($response->Response, $response->Message);
            }
            public function Code0($response) {
                $this->Bad_Request();
            }
            public function Code400($response) {
                $this->Bad_Request($response->Response, $response->Message);
            }
            public function Code401($response) {
                $this->Unauthorized($response->Response, $response->Message);
            }
            public function Code405($response) {
                $this->Method_Not_Allowed($response->Response, $response->Message);
            }
            public function Code500($response) {
                $this->Internal_Server_Error($response->Response, $response->Message);
            }
            public function Code501($response) {
                $this->Not_Implemented($response->Response, $response->Message);
            }

        #endregion

        #region Public Methods

            /**
             * Used when there is a real response
             */
            public function Response() {

                // Format response
                $response = new stdClass();
                $response->Response = $this->Response;
                $response->Message = $this->Message;
                $response->IsGeneric = $this->IsGeneric;
                $response->Code = $this->Code;

                // Check custom properties
                if ($this->CustomProperties !== false)
                    $response = Base_Functions::mergeObjects($response, $this->CustomProperties);

                return $response;

            }

            public function FireResponse($instance = null) {
                if(Base_Functions::IsNullOrEmpty($instance))
                    $instance = $this;

                // Clear buffer
                if (ob_get_contents()) ob_clean();

                // Set status code
                http_response_code($instance->Code);

                // Cache and content-type
                header('Cache-Control: no-cache, must-revalidate');
                header('Content-type: application/json');
                
                // CORS Headers
                header("Access-Control-Allow-Origin: *");
                header("Access-Control-Allow-Credentials: true");
                header("Access-Control-Max-Age: 1000");
                header("Access-Control-Expose-Headers: Refreshed-Jwt");
                header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, Authorization, Accept, Accept-Encoding, Validation-Header-Origin, Payload-Validation-Header, Payload-Validation-Header-Unix");
                header("Access-Control-Allow-Methods: PUT, POST, GET, DELETE");

                // Set response
                $response = json_encode($instance->Response());

                // Log the call
                $this->LogCall($response);

                // Show response
                echo $response;
                exit;

            }

            public function SetCustomProperties($obj = null) {

                // Check that the obj is not null
                if (!Base_Functions::IsNullOrEmpty($obj)) {
                    // Check if object
                    if(!is_object($obj))
                        $obj = (object)$obj;

                    // Unset reserved keyword props
                    if(property_exists($obj, "Response")) unset($obj->Response);
                    if(property_exists($obj, "Message")) unset($obj->Message);
                    if(property_exists($obj, "IsGeneric")) unset($obj->IsGeneric);
                    if(property_exists($obj, "Code")) unset($obj->Code);
                    
                    $this->CustomProperties = $obj;
                }
            }

        #endregion

        #region Private Methods

            /**
             * Set the necessary fields BUT returns only the response object
             */
            private function SetResponse($response, $message = null) {

                // Set properties
                $this->Response = $response;

                // If error concatenate errors messages
                if($this->Code < 200 || $this->Code > 299) {

                    // Push message in errors
                    array_push($this->Errors, $message);
    
                    // Clean errors
                    $this->Errors = array_unique(array_filter($this->Errors));

                    $message = implode(" - ", $this->Errors);
                }

                $this->Success = ($this->Code >= 200 && $this->Code <= 299);
                $this->Message = $message;

                return $response;
            }

            /**
             * Log the call
             */
            private function LogCall($response) {

                // Check if history call is enabled and this call is not Api_History
                if(!defined("HISTORY_CALL_ENABLED") || HISTORY_CALL_ENABLED == false || Base_Functions::HasSubstring($_SERVER["REQUEST_URI"], "Api_History"))
                    return;

                // Init base logs folder
                $base_logs_folder = OFF_ROOT . "/contents/logs/api/";

                // Init logs folder
                $logs_folder = $base_logs_folder . date("Y-m-d") . "/";

                // Check if folder exists
                if(!file_exists($logs_folder)) mkdir($logs_folder, 0777, true);

                // Init logs file
                $logs_file = $logs_folder . "history.json";

                // Get last 3 days
                $dates = [date("Y-m-d"), Base_Functions::FormatDate("Y-m-d", date("Y-m-d"), "-1 day"), Base_Functions::FormatDate("Y-m-d", date("Y-m-d"), "-2 day")];

                // Get folders
                $folders = glob($base_logs_folder . "*", GLOB_ONLYDIR);

                // Delete folders older than 3 days
                foreach($folders as $folder) {

                    // Get folder name
                    $folder_name = str_replace($base_logs_folder, "", $folder);

                    // Check if to delete
                    if(!in_array($folder_name, $dates))
                        Base_Functions::deleteFiles($folder);
                    
                }

                // Create log object
                $obj = new stdClass();
                $obj->Date = date("Y-m-d H:i:s");
                $obj->IP = Base_Functions::get_client_ip();
                $obj->Method = $_SERVER["REQUEST_METHOD"];
                $obj->Uri = $_SERVER["REQUEST_URI"];
                $obj->Code = http_response_code();
                $obj->Response = $response;

                // Get file contents 
                $file_contents = file_exists($logs_file) ? json_decode(file_get_contents($logs_file)) : [];

                // Push object
                array_push($file_contents, $obj);

                // Save file
                file_put_contents($logs_file, json_encode($file_contents));

            }

        #endregion
    }