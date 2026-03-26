<?php

class Base_Curl {

    #region Private variables
        private $curl;
        private $__url;
        private $__type;
        private $__object;
        private $__headers;
        private $__contentType;
        private $__encodeRequest;
        private $__encodeResponse;
        private $__basicAuthUsername;
        private $__basicAuthPassword;
        private $__bearerToken;

        private $__recoursive;

        public $__response_code;
    #endregion

    #region Constructors-Destructors
        public function __construct() {
            $this->resetData();
        }
        public function __destruct() {   
        }
    #endregion

    #region Setter Methods
        public function resetData() {
            $this->__url = '';
            $this->__type = 'GET';
            $this->__object = null;
            $this->__headers = array();
            $this->__contentType = '';
            $this->__encodeRequest = true;
            $this->__encodeResponse = true;
            $this->__bearerToken = null;
            $this->__recoursive = false;
        }

        public function setUrl($url) {
            $url = trim($url);
            if (!$this->IsNullOrEmpty($url))
                $this->__url = $url;
            else
                throw new Exception("Url is empty");

            return $this;
        }
        public function setType($type) {
            $type = trim(strtoupper($type));
            if (!$this->IsNullOrEmpty($type)) {
                $valids = array("GET", "POST", "PUT", "DELETE");
                if (in_array($type, $valids))
                    $this->__type = $type;
                else
                    throw new Exception("Type is not a valid value");    
            }
            else
                throw new Exception("Type is empty");

            return $this;
        }
        public function setObject($object) {
            if (!$this->IsNullOrEmpty($object))
                $this->__object = $object;
            else
                throw new Exception("Object is empty");

            return $this;
        }
        public function setHeaders($headers) {
            if (!$this->IsNullOrEmpty($headers)) {
                if (is_array($headers))
                    $this->__headers = array_merge($this->__headers, $headers);
                else
                    array_push($this->__headers, $headers);
            }
            else
                throw new Exception("Headers are empty");

            return $this;
        }
        public function setContentType($contentType) {
            $contentType = trim(str_replace("Content-Type:", "", $contentType));

            if (!$this->IsNullOrEmpty($contentType))
                $this->__contentType = "Content-Type: $contentType";
            
            else
                throw new Exception("Content-Type is empty");

            return $this;
        }
        public function setEncodeRequest($encodeRequest) {
            if (is_bool($encodeRequest))
                $this->__encodeRequest = $encodeRequest;
            else 
                throw new Exception("EncodeRequest is not a valid value");

            return $this;
        }
        public function setEncodeResponse($encodeResponse) {
            if (is_bool($encodeResponse))
                $this->__encodeResponse = $encodeResponse;
            else 
                throw new Exception("EncodeResponse is not a valid value");

            return $this;
        }
        public function setBasicAuthentication($basicAuthUsername, $basicAuthPassword) {
            if (!$this->IsNullOrEmpty($basicAuthUsername) && !$this->IsNullOrEmpty($basicAuthPassword)) {
                $this->__basicAuthUsername = $basicAuthUsername;
                $this->__basicAuthPassword = $basicAuthPassword;
            }
            else 
                throw new Exception("Basic Authentication params are not valid");

            return $this;
        }
        public function setBearerToken($bearerToken) {
            $bearerToken = trim(str_replace("Bearer", "", $bearerToken));
            if (!$this->IsNullOrEmpty($bearerToken)) {
                $this->__bearerToken = $bearerToken;
            }
            else 
                throw new Exception("Bearer Token is not valid");

            return $this;
        }
    #endregion

    #region Call Methods
        public function call() {

            $this->curl = curl_init();

            $this->setCurlUrl();
            $this->setCurlType();
            $this->setCurlRequest();
            $this->setCurlHeaders();
            $this->setCurlSettings();

            return $this->curlCall();
        }

        private function setCurlUrl() {

            // Check if recoursive
            if($this->__recoursive == false) {
                
                // check param request
                if (!$this->IsNullOrEmpty($this->__object)) {

                    // pass params like object
                    if ($this->__encodeRequest) {
                        $this->__object = json_encode($this->__object, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
                        $this->__contentType = "Content-Type: application/json";
                    }
                    else {

                        // type get concat to url
                        if ($this->__type == 'GET') {

                            if ($this->HasSubstring($this->__url, '?'))
                                $this->__url .= '&';
                            else
                                $this->__url .= '?';

                            $this->__url .= http_build_query($this->__object);
                        }
                    }
                }
            }

            curl_setopt($this->curl, CURLOPT_URL, $this->__url);
            return $this;
        }
        private function setCurlType() {
            if ($this->__type == 'POST')
                curl_setopt($this->curl, CURLOPT_POST, 1);
            else 
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $this->__type);
        }
        private function setCurlRequest() {
            if(!$this->IsNullOrEmpty($this->__object)) {

                // if type GET set request only if __encodeRequest is true
                if ($this->__type != 'GET' || ($this->__type == 'GET' && $this->__encodeRequest))
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->__object);
            }
            elseif($this->__type == 'POST')
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, array());
        }
        private function setCurlHeaders() {
            
            // set content type
            if (!$this->IsNullOrEmpty($this->__contentType))
                array_push($this->__headers, $this->__contentType);

            // set basic authentication
            if (!$this->IsNullOrEmpty($this->__basicAuthUsername) && !$this->IsNullOrEmpty($this->__basicAuthPassword)) {

                $auth = "Authorization: Basic " . base64_encode($this->__basicAuthUsername . ":" . $this->__basicAuthPassword);
                array_push($this->__headers, $auth);
            }
            elseif (!$this->IsNullOrEmpty($this->__bearerToken)) {

                $auth = "Authorization: Bearer " . $this->__bearerToken;
                array_push($this->__headers, $auth);
            }

            $this->__headers = array_values(array_unique($this->__headers));

            // set headers
            if (count($this->__headers) > 0)
                curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->__headers);
        }
        private function setCurlSettings() {

            // Receive server response ...
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

            // Set http referer
            curl_setopt($this->curl, CURLOPT_REFERER, URL_WWW);

            if(IS_DEBUG) {
                curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
            }
        }
        private function curlCall() {
            $ret = null;

            // Get the encoded response
            $server_output = curl_exec($this->curl);

            // Get response code
            $this->__response_code = $response_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

            // Check if 301 or 302 (used for http => https)
            if(($response_code == "301" || $response_code == "302") && $this->__recoursive === false && $this->HasSubstring($this->__url, "http://")) {

                $this->__recoursive = true;

                $this->__url = str_replace("http://", "https://", $this->__url);
                $server_output = $this->setCurlUrl()->call();

                $this->__recoursive = false;
            }

            $this->logCurl($server_output, $response_code);

            if (!$this->__recoursive)
                curl_close($this->curl);

            // Get response
            if($this->__recoursive)
                $ret = $server_output;

            else
                $ret = ($this->__encodeResponse && Base_Functions::IsJson($server_output)) ? json_decode($server_output) : $server_output;

            return $ret;
        }
        private function logCurl($server_output, $response_code) {

            if (class_exists("Base_Logs")) {

                // set log response
                $log_text = "Response Code: $response_code - Response: " . $server_output;

                if($response_code < 200 || $response_code > 299)
                    Base_Logs::Error($log_text);
                else
                    Base_Logs::Info($log_text);
            }
        }
    #endregion

    #region Private Methods
        
        /**
         * If variable is:
         * - string => null or empty
         * - array => without elements
         * - object => without properties
         * 
         * @return true if null or empty
         */
        private function IsNullOrEmpty() {
            $isNullOrEmpty = array();

            // Get args
            $to_check = func_get_args();

            // Check if array
            if(!is_array($to_check)) $to_check = array($to_check);

            // Check fields
            foreach ($to_check as $field) {
                
                if(is_null($field)) {
                    array_push($isNullOrEmpty, true);
                }
                elseif(is_int($field)) {
                    array_push($isNullOrEmpty, false);
                }
                elseif(is_string($field)) {
                    $field = strip_tags(html_entity_decode($field));
                    $field = preg_replace('/\s/', '', $field);
                    array_push($isNullOrEmpty, ($field == null || $field == ""));
                }
                elseif(is_array($field)) {
                    array_push($isNullOrEmpty, (count($field) == 0));
                }
                elseif(is_object($field)) {
                    array_push($isNullOrEmpty, (count((array)$field) == 0));
                }
                elseif(is_bool($field)) {
                    array_push($isNullOrEmpty, false);
                }
                else {
                    array_push($isNullOrEmpty, ($field != null));
                }

            }

            // If even just one element is empty
            return in_array(true, $isNullOrEmpty);
        }

        /**
         * Check if string contain substring
         * @return bool
         */
        private function HasSubstring($string, $substring) {
            return (strpos($string, $substring) !== false);
        }
    #endregion

}