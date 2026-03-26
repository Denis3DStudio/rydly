<?php 

class Base_JWT {

    #region Constructors-Destructors
        public function __construct() {
        }
        public function __destruct() {   
        }
    #endregion

    #region Create Token
        public function generateJWT($payload) {
            $header = new stdClass();
            $header->alg = "HS256";
            $header->typ = "JWT";
            $header->expiration = date("Y-m-d H:i:s", strtotime("+1 month"));

            $headerEncoded = $this->base64UrlEncode(json_encode($header));
            $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
            // Delimit with period (.)
            $dataEncoded = "$headerEncoded.$payloadEncoded";
        
            $rawSignature = hash_hmac('sha256', $dataEncoded, JWT_SECRET, true);
        
            $signatureEncoded = $this->base64UrlEncode($rawSignature);
        
            // Delimit with second period (.)
            $jwt = "$dataEncoded.$signatureEncoded";
        
            return $jwt;
        }
    #endregion

    #region Get Token
        public function getBearerToken() {
            $header = $this->getAuthorizationHeader();

            // Check if found
            if(!Base_Functions::IsNullOrEmpty($header))
                return str_replace("Bearer ", "", $header);

            return null;
        }
        private function getAuthorizationHeader(){
            $header = null;
            if (isset($_SERVER['Authorization'])) {
                $header = trim($_SERVER["Authorization"]);
            }
            else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx or fast CGI
                $header = trim($_SERVER["HTTP_AUTHORIZATION"]);
            }
            elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $header = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
            }
            elseif (function_exists('apache_request_headers')) {
                $requestHeaders = apache_request_headers();
                // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
                $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
                //print_r($requestHeaders);
                if (isset($requestHeaders['Authorization'])) {
                    $header = trim($requestHeaders['Authorization']);
                }
            }
            return $header;
        }
    #endregion

    #region Validate Token
        public function verifyJWT($jwt = null) {
            $ret = new stdClass();
            $ret->Error = "";
            $ret->Valid = false;

            if(!Base_Functions::IsNullOrEmpty($jwt) && $jwt != "undefined") {
                try {
                    list($headerEncoded, $payloadEncoded, $signatureEncoded) = explode('.', $jwt);
                    $dataEncoded = "$headerEncoded.$payloadEncoded";
                    $header = json_decode($this->base64UrlDecode($headerEncoded))->expiration;

                    if(date("Y-m-d H:i:s") < $header) {
                        $signature = $this->base64UrlDecode($signatureEncoded);
                        $rawSignature = hash_hmac('sha256', $dataEncoded, JWT_SECRET, true);
                    
                        if(!$this->hash_equals($rawSignature, $signature)) {
                            $ret->Error = "NOT EQUALS";
                        } else {
                            $ret->Valid = true;
                        }
                    } else {
                        $ret->Error = "EXPIRATION";
                    }
                } catch (\Throwable $th) {
                    $ret->Error = "EXCEPTION";
                }
            } else {
                $jwt2 = $this->getBearerToken();
                if(!Base_Functions::IsNullOrEmpty($jwt2) && $jwt != "undefined") {
                    return $this->verifyJWT($jwt2);
                }
            }

            return $ret;
        }
    #endregion

    #region Get Payload
        public function getPayload($jwt = null) {
            $ret = null;
            $jwt = ($jwt == null || $jwt == "") ? $this->getBearerToken() : $jwt;
 
            if(!Base_Functions::IsNullOrEmpty($jwt)) {
                try {
                    list($headerEncoded, $payloadEncoded, $signatureEncoded) = explode('.', $jwt);
                    
                    $ret = json_decode($this->base64UrlDecode($payloadEncoded));

                } catch (\Throwable $th) {
                    
                }
            }

            return $ret;
        }
        public function getExpiration($jwt = null) {
            $ret = null;
            $jwt = ($jwt == null || $jwt == "") ? $this->getBearerToken() : $jwt;
 
            if(!Base_Functions::IsNullOrEmpty($jwt)) {
                try {
                    list($headerEncoded, $payloadEncoded, $signatureEncoded) = explode('.', $jwt);
                    
                    $ret = json_decode($this->base64UrlDecode($headerEncoded))->expiration;
                } catch (\Throwable $th) {
                    
                }
            }

            return $ret;
        }
    #endregion


    #region Private Methods
        private function base64UrlEncode($data) {
            $urlSafeData = strtr(base64_encode($data), '+/', '-_');
        
            return rtrim($urlSafeData, '='); 
        }
        private function base64UrlDecode($data) {
            $urlUnsafeData = strtr($data, '-_', '+/');
            $paddedData = str_pad($urlUnsafeData, strlen($data) % 4, '=', STR_PAD_RIGHT);
        
            return base64_decode($paddedData);
        }
        private function hash_equals($str1, $str2) {
            if(strlen($str1) != strlen($str2)) {
                return false;
            } else {
                $res = $str1 ^ $str2;
                $ret = 0;
                for($i = strlen($res) - 1; $i >= 0; $i--) $ret |= ord($res[$i]);
                return !$ret;
            }
        }
    #endregion

}
