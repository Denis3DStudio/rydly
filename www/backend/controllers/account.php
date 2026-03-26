<?php

    namespace Controller\Account;

use Base_Auth;
use Base_Controller;
    use Base_Functions;
    use Base_Session;

    class Methods extends Base_Controller {

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }
        #endregion

        #region Public Methods

            public function login($response) {

                // Check if login has been successful
                if(property_exists($this->Response, "Success") && !$this->Response->Success)
                    return;
                
                // Build define name
                $session_name = Session_Name_Current();

                // Check if already exists
                $session = Session_Get($session_name);

                if(!Base_Functions::IsNullOrEmpty($session))
                    Session_Remove($session_name);

                // Init session
                $session = new Base_Session($session_name);

                // Set session
                foreach ($response as $key => $value) {
                    $session->setAttribute($key, $value);
                }

                // Delete cookie remember me name
                Base_Auth::deleteCookie(Base_Functions::getCookieName(true));

            }

            public function impersonate($response) {

                $this->login($response);
            }

            public function logout() {

                // Build define name
                $session_name = Session_Name_Current();

                // Check if already exists
                $session = Session_Get($session_name);

                if(!Base_Functions::IsNullOrEmpty($session))
                    Session_Remove($session_name);

                // Delete cookie session
                Base_Auth::deleteCookie(Base_Functions::getCookieName());

                // Delete cookie remember me name
                Base_Auth::deleteCookie(Base_Functions::getCookieName(true));

                $this->Success();
                $this->FireResponse();
            }

        #endregion

        #region Private Methods
        
        #endregion

    }

?>