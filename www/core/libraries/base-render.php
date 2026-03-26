<?php

    class Base_Render {

        #region Properties

            // LINQ Helper
            public $__linq;

            // Operations Helper
            public $__opHelper;

            // API Response
            public $Success;
            public $Code;
            public $Message;

            public $Params;
            public $Pages;
            public $Scripts;
            public $Logged;

            public $Title;
            public $Description;
            public $OG;

            private $__external_props;

        #endregion

        #region Constructors-Destructors

            public function __construct() {

                $this->__linq = new Base_LINQHelper();
                $this->__opHelper = new Base_OperationsHelper();
                $this->Success = true;
                $this->Params = (object)Request::Get();

                // Init external props
                $this->__external_props = new stdClass();
            }
            public function __destruct() {
            }

        #endregion

        #region Set/Get

            public function __set($name, $value) {
                $this->__external_props->{$name} = $value;
            }
            public function __get($name) {

                // Check if already exists
                if(property_exists($this->__external_props, $name))
                    return $this->__external_props->{$name};

                // Build path
                $path = ACTIVE_FULL_PATH . "/render/$name.php";

                // Get instance
                $instance = Base_Functions::IncludeExternalMethods($path);
                
                // Set to external props
                $this->__external_props->{$name} = $instance;

                return $instance;
            }

        #endregion

        #region Public Methods

            public function api($name) {

                // Get original value of $_GET
                $originalGet = $_GET;

                // Unset $_GET
                foreach ($_GET as $key => $value)
                    unset($_GET[$key]);

                // Get the arguments
                $args = func_get_args();

                // Remove the first argument
                array_shift($args);

                // Build request params
                if(count($args) > 0) {
                    foreach ($args[0] as $key => $value)
                        $_GET[$key] = $value;
                
                    // Generate payload validation header
                    $this->generatePayloadValidationHeader($args[0]);
                    
                }

                // Generate JWT
                $this->generateJWT();

                // Build request uri
                $_GET["REQUEST_URI"] = ACTIVE_PATH . "-api/" . $name;

                // Call base api and get response
                $response = (new Base_Api(ltrim(strtolower(ACTIVE_PATH), "/")))->Response;

                // Ripristinate original value of $_GET
                $_GET = $originalGet;

                // Set response props
                $this->Success = $response->Success;
                $this->Code = $response->Code;
                $this->Message = $response->Message;

                // Return response
                return $response->Response()->Response;
            }
        
        #endregion

        #region Private Methods

            private function generatePayloadValidationHeader($request) {

                // Create key
                $crypting_key = time() + 3;

                // Format value
                $request_format = Request::FormatRequestForValidationHeader($request);

                // Encode request
                $request_encoded = json_encode($request_format, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

                // Hash request
                $request_hashed = hash('sha256', $request_encoded);

                // Encrypt
                $value = Base_Encryption::Encrypt($request_hashed, $crypting_key);

                // Set header
                $_SERVER["Payload-Validation-Header"] = $value;
                $_SERVER["Validation-Header-Origin"] = "SERVER";

            }
            private function generateJWT() {

                // Get session
                $session = Base_Auth::getSession();
                
                // Create JWT
                if(!Base_Functions::IsNullOrEmpty($session))
                    $_SERVER["Authorization"] = "Bearer " . (new Base_JWT())->generateJWT($session);

            }

        #endregion

    }