<?php

class Base_Logs {

    #region Public

        public static function Info($value, $method = null) {
            self::WriteLog($value, "I", $method);
        }
        public static function Error($value, $method = null) {
            self::WriteLog($value, "E", $method);
        }

        public static function Logged($id = '') {

            // check session start
            if (session_status() == PHP_SESSION_NONE)
                session_start();

            if (!isset($_SESSION['LOGS_CURRENT_USER_ID']) || !is_array($_SESSION['LOGS_CURRENT_USER_ID']))
                $_SESSION['LOGS_CURRENT_USER_ID'] = array();

            $_SESSION['LOGS_CURRENT_USER_ID'][$_SERVER['SERVER_NAME']] = ($id != '' && $id != null && $id != 0) ? $id : '';
        }

    #endregion

    #region Private

        private static function WriteLog($value, $type, $method) {

            self::DeleteOldFiles();

            // Check if set the verbose logs
            if($type == "I" && defined("VERBOSE_LOGS") && VERBOSE_LOGS == false)
                return;

            // check session start
            if (session_status() == PHP_SESSION_NONE)
                session_start();

            try {
                // Define path
                $parameterBasePath = $_SERVER["DOCUMENT_ROOT"] . '/..' . '/contents/logs/' . str_replace("www.", "", $_SERVER['SERVER_NAME']);

                // Check folder
                if(!file_exists($parameterBasePath))
                    mkdir($parameterBasePath, 0755, true);

                // Get log file path
                $logFile = $parameterBasePath . "/" . date("Ymd") . ".json";

                // Check session
                if(!isset($_SESSION['LOGS_CURRENT_USER_ID'][$_SERVER['SERVER_NAME']]))
                    self::Logged();

                // Get logged id
                $who = $_SESSION['LOGS_CURRENT_USER_ID'][$_SERVER['SERVER_NAME']];

                // Check log type
                if(strtoupper(trim($type)) == "I" || strtoupper(trim($type)) == "E") $type = (strtoupper(trim($type)) == "I") ? "INFO" : "ERROR";
                else $type = "";

                // Get call stacks
                $debug_stacks = array_reverse(debug_backtrace(0, 6));

                // Init stack 
                $stacks = array();

                // Get before the base logs
                foreach ($debug_stacks as $st) {

                    if($st['class'] != "Base_Logs") {
                        $stack = $st;

                        // Create text
                        $text = trim(str_replace($_SERVER["DOCUMENT_ROOT"], "", $st['file'])) . " - " . $st["line"] . " > " . $st["class"] . " > " . $st["function"];
                        array_push($stacks, $text);
                    }
                    else
                        break;
                }

                // Get already stored logs
                $logs = file_exists($logFile) ? json_decode(file_get_contents($logFile)) : [];

                // Check if is null
                if($logs == null) $logs = array();

                // Create obj
                $obj = new stdClass();
                $obj->Id = count($logs) + 1;
                $obj->Time = date("H:i:s");
                $obj->Server = $_SERVER['SERVER_NAME'];
                $obj->Path = $stacks;
                $obj->Method = ($method == null) ? trim($stack['function']) : $method;
                $obj->RequestMethod = $_SERVER["REQUEST_METHOD"];
                $obj->Logged = trim(str_replace("|", "", $who));
                $obj->Referer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : null;
                $obj->Type = $type;
                $obj->IsResponse = ($stack["class"] == "Base_Responses");
                $obj->Value = trim(gettype($value) != "string" ? json_encode($value) : $value);

                if (strlen($obj->Value) > 100000)
                    $obj->Value = substr($obj->Value, 0, 100000);

                // Push
                array_push($logs, $obj);

                // Write content
                file_put_contents($logFile, json_encode($logs));
            }
            catch (Exception $e) {
            }
        }

        private static function DeleteOldFiles() {

            // Define path
            $parameterBasePath = $_SERVER["DOCUMENT_ROOT"] . '/..' . '/contents/logs/' . str_replace("www.", "", $_SERVER['SERVER_NAME']);

            if (file_exists($parameterBasePath)) {

                // get files
                $files = scandir($parameterBasePath);

                // Remove useless paths
				if (($key = array_search(".", $files)) !== false ) unset($files[$key]);
				if (($key = array_search("..", $files)) !== false ) unset($files[$key]);
				if (($key = array_search(".DS_Store", $files)) !== false ) unset($files[$key]);

                // set expired files created more than 2 months ago
                $expired = date("Ymd", strtotime("-2 months"));

                foreach ($files as $file) {
                    
                    if (str_replace(".json", "", $file) < $expired && file_exists("$parameterBasePath/$file"))
                        unlink("$parameterBasePath/$file");
                }
            }
        }

    #endregion
}