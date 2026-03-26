<?php

    class Base_Auth {

        public static function sessionCookieName() {

            // Calculate 1 week in seconds
            $lifetime = (3600 * 24 * 7);

            // Calculate today + 1 week
            $expire = time() + $lifetime;

            // Get session cookie name
            $name = Base_Functions::getCookieName();

            // Check if already isset
            if (isset($_COOKIE[$name]))
                session_id($_COOKIE[$name]);

            // Check if session is not active
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_name($name);
                session_start();
            }

            // Update session cookie
            self::createCookie($name, session_id(), $expire);

        }
        public static function getSession($exclude_class_variables = false) {

            // Build define name
            $name = strtoupper(ltrim(ACTIVE_PATH, "/")) . "_SESSION";

            // Get session
            $session = Session_Get(constant($name));

            // Check if cookie is to set OR Type is ANONYMOUS (class Base_Customer_Type)
            if(Base_Functions::IsNullOrEmpty($session) || (property_exists($session, "Type") && $session->Type == 1))
                $session = self::checkCookieRememberMe(constant($name), $session);

            // Already set, set cookie remember me if not ANONYMOUS (class Base_Customer_Type)
            elseif(!Base_Functions::IsNullOrEmpty($session) && Request::GetHeader("Validation-Header-Origin") == "EXTERNAL" && (!property_exists($session, "Type") || $session->Type != 1))
                self::setCookieRememberMe($session);

            // Return attributes as session
            return !Base_Functions::IsNullOrEmpty($session) ? $session->getAttributes($exclude_class_variables) : null;

        }
        public static function getSessionId() {

            // Get session cookie name
            $name = Base_Functions::getCookieName();

            // Check if already isset
            if(isset($_COOKIE[$name]))
                return $_COOKIE[$name];

            return "";

        }
        
        private static function setCookieRememberMe($session) {
            $name = Base_Functions::getCookieName(true);

            // Create payload content crypted
            $payload = Base_Encryption::Encrypt(json_encode($session->getAttributes()));

            // Get cookie value and check if is the same payload
            if(isset($_COOKIE[$name]) && $_COOKIE[$name] == $payload)
                return;

            // Calculate 5 days in seconds
            $expire = time() + (86400 * 5);

            // Set cookie
            self::createCookie($name, $payload, $expire);

        }
        private static function checkCookieRememberMe($session_name, $session) {
            $name = Base_Functions::getCookieName(true);

            // Check if isset
            if(isset($_COOKIE[$name])) {

                // Get payload
                $payload = json_decode(Base_Encryption::Decrypt($_COOKIE[$name]));

                // Check session name
                if(!Base_Functions::IsNullOrEmpty($payload) && property_exists($payload, "__sessionName") && $payload->__sessionName == $session_name) {

                    // Remove from payload the session name
                    unset($payload->__sessionName);

                    if(!Base_Functions::IsNullOrEmpty($session))
                        Session_Remove($session_name);

                    // Set session
                    $session = new Base_Session($session_name);

                    // Set attributes
                    foreach ($payload as $key => $value)
                        $session->setAttribute($key, $value);

                }
                
            }
            
            return $session;
        }

        private static function createCookie($name, $payload, $expires) {

            // Get params
            $params = self::getCookieParams();

            // Set expiration time
            $params['expires'] = $expires;
            
            // Set cookie
            setcookie($name, $payload, $params);

        }
        public static function deleteCookie($name) {

            if(!isset($_COOKIE[$name])) return;

            // Unset cookie
            unset($_COOKIE[$name]);

            // Call create cookie with empty value and expired time
            self::createCookie($name, "", time() - 3600);

        }
        private static function getCookieParams() {
            $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ||
                       (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ||
                       (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on');
        
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $host = preg_replace('/^www\./', '', $host);
            $parts = explode('.', $host);
            $domain = (count($parts) >= 2) ? '.' . $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1] : $host;
        
            return [
                'path' => '/',
                'domain' => $domain,
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Strict',
            ];
        }

    }