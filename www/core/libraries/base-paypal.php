<?php

class Base_PayPal {

    public $BaseUrl;
    public $Version;
    public $ClientId;
    public $ClientSecret;
    public $Successful;

    private $Token;

    #region Constructors-Destructors
    
        public function __construct() {

            $this->Version = PAYPAL_API_VERSION;
            $this->BaseUrl = PAYPAL_URL . "/" . $this->Version;
            $this->ClientId = PAYPAL_CLIENT_ID;
            $this->ClientSecret = PAYPAL_CLIENT_SECRET;
            $this->Successful = true;

            $this->Token = "";
        }
        public function __destruct() {
        }
        
    #endregion

    #region Setters
        
        private function changeVersion($version = null) {

            // if null reset version to orginal
            if ($this->IsNullOrEmpty($version))
                $version = PAYPAL_API_VERSION;

            $this->Version = $version;

            // Rebuild base url
            $this->setBaseUrl();
        }
        private function setBaseUrl() {
            $this->BaseUrl = PAYPAL_URL . "/" . $this->Version;
        }

    #endregion

    #region Public Methods

        /**
         * Login and get bearer token
         * @link https://developer.paypal.com/api/rest/authentication/
         * @param object $credentials Object containing specifications to get bearer token
         * @return object 
        */
        public function login() {

            // Set version
            $this->changeVersion("v1");

            // Create object to get credentials
            $credentials = new stdClass();
            $credentials->grant_type = "client_credentials";

            $response = $this->Call("/oauth2/token", "POST", http_build_query($credentials), "application/x-www-form-urlencoded", false, false);

            // Reset version
            $this->changeVersion();

            // Save Bearer Token
            if (property_exists($response, "token_type") && $response->token_type == "Bearer")
                $this->Token = $response->access_token;
            // Set as failed
            else
                $this->Successful = false;

            return $response;
        }
        /**
         * Create Order
         * @link https://developer.paypal.com/docs/api/orders/v2/#orders_create
         * @param object $order Object order
         * @return object order object with payment link
        */
        public function createOrder($order) {
            return $this->Call("/checkout/orders", "POST", $order);
        }
        /**
         * Create Order
         * @link https://developer.paypal.com/docs/api/orders/v2/#orders_capture
         * @param string $token token of order's transaction
         * @return object caputer object
        */
        public function capturePayment($token) {
            return $this->Call("/checkout/orders/$token/capture", "POST", null, "application/json");
        }
        
    #endregion

    #region Private Methods
        
        /**
         * cUrl call to PayPal
        */
        public function Call($url, $type = "GET", $object = null, $content_type = null, $encode_request = null, $bearer = true) {

            try {

                // Set encode request
                if ($this->IsNullOrEmpty($encode_request))
                    $encode_request = (strtoupper($type) != "GET");

                // Create API Url
                $url = $this->BaseUrl . $url;

                $base_curl = new Base_Curl();
                $base_curl->setUrl($url)->setType($type)->setEncodeRequest($encode_request);

                if (!Base_Functions::IsNullOrEmpty($object))
                    $base_curl->setObject($object);

                if (!Base_Functions::IsNullOrEmpty($content_type))
                    $base_curl->setContentType($content_type);

                if ($bearer) {
                    $base_curl->setBearerToken($this->Token);
                    // $base_curl->setHeaders("PayPal-Request-Id: " . substr(base64_encode($this->Token), 0, 36));
                } else
                    $base_curl->setBasicAuthentication($this->ClientId, $this->ClientSecret);

                $ret = $base_curl->call();

                if ($base_curl->__response_code < 200 || $base_curl->__response_code > 299)
                    $this->Successful = false;

                return $ret;
            } catch (\Throwable $th) {

                Base_Logs::Error("Stripe - " . json_encode($th));

                return null;
            }
        }

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
            if (!is_array($to_check)) $to_check = array($to_check);

            // Check fields
            foreach ($to_check as $field) {

                if (is_null($field)) {
                    array_push($isNullOrEmpty, true);
                } elseif (is_int($field)) {
                    array_push($isNullOrEmpty, false);
                } elseif (is_string($field)) {
                    $field = strip_tags(html_entity_decode($field));
                    $field = preg_replace('/\s/', '', $field);
                    array_push($isNullOrEmpty, ($field == null || $field == ""));
                } elseif (is_array($field)) {
                    array_push($isNullOrEmpty, (count($field) == 0));
                } elseif (is_object($field)) {
                    array_push($isNullOrEmpty, (count((array)$field) == 0));
                } elseif (is_bool($field)) {
                    array_push($isNullOrEmpty, false);
                } else {
                    array_push($isNullOrEmpty, ($field != null));
                }
            }

            // If even just one element is empty
            return in_array(true, $isNullOrEmpty);
        }
        
    #endregion
}
