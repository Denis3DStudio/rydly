<?php

    namespace Render\Login;

    use Base_Render;
    use Base_Functions;

    class Methods extends Base_Render {

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }
        #endregion

        #region Public Methods

            public function index() {

                // Check if the user is already logged in
                if (property_exists($this, "Logged") && !Base_Functions::IsNullOrEmpty($this->Logged) && ((class_exists("Base_Customer_Type") && $this->Logged->Type == \Base_Customer_Type::LOGGED) || $this->Logged->Type == 2) ) {
                    // Redirect to the dashboard
                    header("Location: " . ACTIVE_PATH . "/dashboard");
                    exit();
                }

                return true;
            }

        #endregion

    }

?>