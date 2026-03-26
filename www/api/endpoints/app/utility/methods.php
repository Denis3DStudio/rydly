<?php

    namespace App\Utility;

    use Base_Cache;
    use Base_Encryption;
    use Base_Functions;
    use Base_Methods;
    use ReflectionClass;
    use stdClass;

    class Methods extends Base_Methods {

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }
        #endregion

        #region Public Methods

            // Get
            public function getAppSettings($version, $translationVersion, $idLanguage) {
                $response = new stdClass();
                $response->Settings = new stdClass();

                // Format
                $response->ToUpdate = !($this->checkVersion($version));
                $response->ToUpdateTranslation = Base_Functions::IsNullOrEmpty($translationVersion) ? true : !($this->checkVersion($translationVersion, true));

                // Create anonymous account
                $response->Account = $this->createAnonymousAccount($idLanguage);

                // Get all data of the settings
                $settings = $this->__linq->selectDB("Property, Content")->fromDB("settings")->whereDB("SendApp = 1")->getResults();

                // Cycle all settings
                foreach($settings as $setting)
                    $response->Settings->{$setting->Property} = $setting->Content;

                // Add translation version
                $response->Settings->TranslationVersion = $this->calculateTranslationCurrentVersion();
                $response->Enums = $this->getEnum();
                $response->APIs = $this->getApi();

                return $this->Success($response);
            }
            public function getTranslations($idLanguage) {

                // Generate cache filename
                $cache_filename = "TRANSLATIONS_$idLanguage" . "_" . $this->calculateTranslationCurrentVersion();

                // Get cache
                $cache = Base_Cache::get($cache_filename);

                // Check if cache is valid
                if ($cache !== false)
                    return $this->Success($cache);

                // Create an response array
                $response = new stdClass();

                // Get translations
                $sql = "SELECT CONCAT(t.Section, '.', t.Page, '.', t.Label) AS TKey, tl.Translation AS Value
                        FROM translations t
                        INNER JOIN translations_languages tl ON t.IdTranslation = tl.IdTranslation AND tl.IdLanguage = $idLanguage
                        WHERE t.IsValid = 1 AND t.Section IN ('APP', 'COMMON')";
                $translations = $this->__linq->queryDB($sql)->getResults();

                // Format
                foreach ($translations as $translation)
                    $response->{$translation->TKey} = str_replace("<br>", "\n", $translation->Value);

                // Save cache
                Base_Cache::set($cache_filename, $response);

                // Return the array of the languages
                return $this->Success($response);
            }

        #endregion

        #region Private Methods

            private function checkVersion($version, $isTranslation = false) {

                // Check if translation version
                if($isTranslation) {

                    // Get current translation version
                    $current_version = $this->calculateTranslationCurrentVersion();

                    // Check the version of the app
                    return $version >= $current_version;
                }

                // Format version
                $version = number_format(floatval($version), 1);

                // Get current app version
                $current_version = $this->__linq->selectDB("Content")->fromDB("settings")->whereDB("Property = 'Version'")->getFirstOrDefault()->Content;    

                // Split the version by "."
                $versions_numbers = explode(".", $version);

                // Split the version by "."
                $db_versions_numbers = explode(".", $current_version);

                $validity = true;

                // Check if the first number is bigger the first from the db
                if ($versions_numbers[0] <= $db_versions_numbers[0]) {

                    // Check if not bigger
                    if ($versions_numbers[0] < $db_versions_numbers[0])
                        $validity = false;
                    else if ($versions_numbers[1] < $db_versions_numbers[1])
                        $validity = false;
                }

                // Check the version of the app
                return $validity;
            }
            private function calculateTranslationCurrentVersion() {

                // Get last updated translation
                $last_updated = $this->__linq->selectDB("UpdateDate")->fromDB("translations")->whereDB("1=1 ORDER BY UpdateDate DESC")->getFirstOrDefault()->UpdateDate;

                // Cast to timestamp
                return strtotime($last_updated);

            }

            private function getEnum() {

                $response = new stdClass();

                // Get all files in enums folder
                $enums = glob($_SERVER["DOCUMENT_ROOT"] . "/enums/*.php");

                foreach ($enums as $enum) {

                    // Include file to ensure class is loaded
                    include_once $enum;
                
                    // Get file content
                    $content = file_get_contents($enum);
                
                    // Match all class names in the file
                    preg_match_all('/class\s+(\w+)(.*)?\{/', $content, $matches);
                
                    // Check if there are matches
                    if (isset($matches[1]) && count($matches[1]) > 0) {
                        foreach ($matches[1] as $className) {
                            
                            // Check if class exists
                            if (class_exists($className)) {
                
                                // Init response for the class
                                $response->{strtoupper($className)} = new stdClass();
                
                                // Get class reflection
                                $class = new ReflectionClass($className);
                
                                // Get constants
                                $constants = $class->getConstants();
                
                                // Add keys to response
                                foreach ($constants as $key => $value) {
                                    $response->{strtoupper($className)}->{$key} = $value;
                                }
                            }
                        }
                    }
                }
                
                return $this->Success(json_encode($response));
            }
            private function getApi() {

                $response = new stdClass();

                // Init projects to keep
                $projects = [ltrim(ACTIVE_PATH, "/")];

                // Check if exists the DEFINE called SHARED_API
                if(defined("SHARED_API")) {
                    $shared = SHARED_API;
                    
                    // Check if array
                    if(!is_array(SHARED_API))
                        $shared = [$shared];

                    // Check if exists
                    foreach ($shared as $project) {
                        
                        if(file_exists($_SERVER["DOCUMENT_ROOT"] . API_FOLDER . "/routes/$project-routes.json"))
                            array_push($projects, $project);

                    }

                    // Remove duplicates
                    $projects = array_values(array_unique($projects));
                }

                // Loop projects
                foreach ($projects as $key => $project) {

                    // Check if first (the default project)
                    $isBase = $key == 0;

                    // Build enum
                    $enums = $this->buildAPIEnums($isBase, $project);

                    // Init variables
                    $__API = $enums[0];

                    // Init apis
                    $apis = new stdClass();

                    // Format json to print
                    foreach ($__API as $method_apis) {

                        foreach ($method_apis as $controller => $endpoints) {
                            
                            // Check if the hash exists in the apis object
                            if(!property_exists($apis, $controller))
                                $apis->{$controller} = new stdClass();
        
                            foreach ($endpoints as $key1 => $endpoint) {
                                $apis->{$controller}->{$key1} = $isBase ? $endpoint->Url : str_replace("$project-", $projects[0] . "-", $endpoint->Url);
                            }
                        }

                    }

                    // Add to response
                    $response->{strtoupper($project)} = $apis;
                    
                }

                return $this->Success(json_encode($response));

            }
            private function buildAPIEnums($setBaseVariables = true, $partial_path = null) {

                // Check if partial path is null
                if(Base_Functions::IsNullOrEmpty($partial_path))
                    $partial_path = ltrim(ACTIVE_PATH, "/");

                // Format partial path
                $partial_path = strtolower($partial_path);

                // Build routes file name
                $name = $partial_path . "-routes.json";

                // Build path
                $path = $_SERVER["DOCUMENT_ROOT"] . API_FOLDER . "/routes/$name";

                // Check if exists
                if(!file_exists($path))
                    return;
                
                // Get routes
                $routes = Base_Functions::APIRoutesReorder(json_decode(file_get_contents($path)));

                // Init object
                $__API_requests = new stdClass();
                $__API = new stdClass();

                // Get types
                $types = array_keys((array)$routes);

                foreach ($types as $type) {
                    $upper_type = strtoupper($type);

                    // Init type
                    $__API_requests->{$upper_type} = new stdClass();
                    $__API->{$upper_type} = new stdClass();

                    // Get controllers
                    $controllers = array_keys((array)$routes->{$type});

                    foreach ($controllers as $controller) {
                        $upper_controller = strtoupper($controller);
                        $endpoint_exists = false;

                        // Build path
                        $path = $_SERVER["DOCUMENT_ROOT"] . API_FOLDER . "/endpoints/$partial_path/" . strtolower($controller) . "/methods.php";

                        // Check if folder exists
                        if(file_exists($path))
                            $endpoint_exists = true;

                        // Build
                        $__API->{$upper_type}->{$upper_controller} = property_exists($__API->{$upper_type}, $upper_controller) ? $__API->{$upper_type}->{$upper_controller} : new stdClass();

                        // Get endpoints
                        $endpoints = array_keys((array)$routes->{$type}->{$controller});

                        foreach ($endpoints as $endpoint) {
                            $upper_endpoint = strtoupper($endpoint);

                            // Check if INDEX method
                            if(Base_Functions::IsNullOrEmpty($upper_endpoint))
                                $upper_endpoint = "INDEX";
                            
                            // Get method
                            $method = $routes->{$type}->{$controller}->{$endpoint};

                            // Build api url
                            $url = rtrim(str_replace("//", "/", implode("/", ["", $partial_path . "-api", $controller, $endpoint])), "/");

                            // Encrypt url
                            $encrypted_url = Base_Encryption::Encrypt($url);

                            // Check method name
                            $method_name = property_exists($method, "Method") ? $method->Method : strtolower($upper_type);

                            // Build objects
                            $__API->{$upper_type}->{$upper_controller}->{$upper_endpoint} = new stdClass();
                            $__API->{$upper_type}->{$upper_controller}->{$upper_endpoint}->Url = $url . "/";
                            $__API->{$upper_type}->{$upper_controller}->{$upper_endpoint}->Method = $method_name;
                            $__API->{$upper_type}->{$upper_controller}->{$upper_endpoint}->Valid = $endpoint_exists;

                            $__API_requests->{$upper_type}->{$encrypted_url} = new stdClass();
                            $__API_requests->{$upper_type}->{$encrypted_url}->Method = $method_name;
                            $__API_requests->{$upper_type}->{$encrypted_url}->Auth = property_exists($method, "Auth") ? $method->Auth : false;

                            // Check if has request
                            if(property_exists($method, "Request") && !Base_Functions::IsNullOrEmpty($method->Request)) {
                                $path = "";

                                // Full Path
                                if(Base_Functions::HasSubstring($method->Request->Path, "/"))
                                    $path = str_replace("//", "/", implode("/", ["", API_FOLDER, "endpoints", $partial_path, str_replace(".json", "", $method->Request->Path) . ".json"]));

                                // Partial
                                else
                                    $path = str_replace("//", "/", implode("/", ["", API_FOLDER, "endpoints", $partial_path, strtolower($controller), "requests", str_replace(".json", "", $method->Request->Path) . ".json"]));

                                // Set object
                                $__API_requests->{$upper_type}->{$encrypted_url}->Class = $method->Request->Class;
                                $__API_requests->{$upper_type}->{$encrypted_url}->Path = $path;

                            }

                        }
                    }
                    
                }

                // Check if set base variables
                if($setBaseVariables) {

                    // Set objects
                    $this->__API_requests = $__API_requests;
                    $this->__API = $__API;

                }
                
                return [$__API, $__API_requests];
            }

            public function createAnonymousAccount($idLanguage) {

                // Create anonymous account
                $account = new stdClass();
                $account->IdAccount = Base_Functions::UniqueToken();
                $account->Type = class_exists("Base_Customer_Type") ? \Base_Customer_Type::ANONYMOUS : 1;
                $account->IdLanguage = $idLanguage;

                // Add 'Backup' Token
                $account->Token = Base_Encryption::Encrypt(json_encode($account));

                // Create the JWT from the account obj
                $account->JWT = (new \Base_JWT())->generateJWT($account);

                return $account;
            }

        #endregion

    }