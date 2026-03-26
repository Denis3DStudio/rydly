<?php

    class Base_Config {

        private $__url;

        #region Constructors-Destructors

            public function __construct() {

                // Init url
                $this->__url = null;

                $this->Config();
            }
            public function __destruct() {}
            
        #endregion

        #region Config Methods

            public function Config() {

                // Off Root Path
                if(!defined("OFF_ROOT")) {
                    $exp = explode("/", $_SERVER["DOCUMENT_ROOT"]);
                    array_pop($exp);
                    $path = join("/", $exp);

                    // Check if config folder exists off root or use as default if even if root not exists
                    if(file_exists($path . "/config") || !file_exists($_SERVER["DOCUMENT_ROOT"] . "/config"))
                        DEFINE("OFF_ROOT", $path);

                    // Set root
                    else
                        DEFINE("OFF_ROOT", $_SERVER["DOCUMENT_ROOT"]);
                }
        
                // Include base-config.php
                $config = $this->Parse(OFF_ROOT . "/config/base/base-config.json");
        
                // Get params
                $params = $this->Files();

                // Check if found any params
                if(Base_Functions::IsNullOrEmpty($params))
                    throw new Exception("No config file found", 1);
        
                // Check if prod or sandbox
                $config = (object)array_merge((array)$config, (array)(property_exists($params, "Prod") && $params->Prod ? $this->Parse(OFF_ROOT . "/config/base/config-prod.json") : $this->Parse(OFF_ROOT . "/config/base/config-dev.json")));
        
                // Create define variables
                $this->Define($config, $params);

            }
        
            private function Parse($file) {
        
                // Check if exists
                if(file_exists($file)) {
        
                    // Get file content
                    $content = file_get_contents($file);
        
                    try {
                        return json_decode($content);
                    } catch (\Throwable $th) {
                        throw new Exception("File `$file` not well formatted", 1);
                    }
        
                }
        
                throw new Exception("File `$file` not exists", 1);
        
            }
        
            private function Files() {
                $response = new stdClass();
        
                // Get config files
                $files = glob(OFF_ROOT . "/config/*.json");
        
                foreach ($files as $file) {
                    $params = $this->Parse($file);
                        
                    // Check url
                    if(property_exists($params, "Url") && $params->Url != null) {
        
                        // Check if same host domain
                        if($this->CheckHost($params->Url)) {
        
                            $response = $params;
        
                            break;
        
                        }
        
                    }
        
                }
        
                return $response;
            }
        
            private function CheckHost($urls) {
                $response = false;

                // Check if is an array
                if(!is_array($urls)) $urls = [$urls];

                if(count($urls) == 0)
                    return $response;

                // Get current urls
                $current_url = $this->FormatHost("http://" . $_SERVER['SERVER_NAME']);
        
                foreach ($urls as $url) {
                    
                    // Format
                    $format = $this->FormatHost($url);

                    if($format == $current_url) {
                        $response = true;
                        $this->__url = $url;
                        break;
                    }

                }
        
                return $response;
            }

            private function FormatHost($domain) {

                // Parse
                $url = parse_url($domain);
            
                // Explode
                $host_names = explode(".", $url["host"]);
        
                // Exclude www and subdomain
                return $host_names[count($host_names)-2] . "." . $host_names[count($host_names)-1];

            }
        
            private function Define($config, $params) {
                $settings = new stdClass();

                // Url
                if(!defined("URL_WWW"))
                    DEFINE("URL_WWW", rtrim($this->__url, "/"));

                // Format config and params
                $config = $this->formatConfig($config);
                $params = $this->formatConfig($params);

                // Merge and override config with params
                $settings = Base_Functions::mergeObjects($config, $params);

                // Create dynamic define by config
                foreach ($settings as $key => $value) {
                    
                    // Check if already defined
                    if(!defined(strtoupper($key)))
                        DEFINE(strtoupper($key), $value);
        
                }
        
            }

            private function formatConfig($config, $response = null, $prepend = "") {
                if(Base_Functions::IsNullOrEmpty($response))
                    $response = new stdClass();

                // Format
                foreach ($config as $key => $value) {
                    
                    // Check if is object
                    if(is_object($value))
                        $response = $this->formatConfig($value, $response, $prepend . $key . "_");

                    else
                        $response->{$prepend . $key} = $value;

                }

                return $response;
            }

        #endregion

    }