<?php

    namespace Render\Email;

    use Base_Render;

    class Methods extends Base_Render {

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }
        #endregion

        #region Public Methods

            public function detail() {

                // Get the email data
                $email = $this->api("Email", $this->Params);

                if ($this->Success == true)
                    return $email;

                return false;
            }

        #endregion

    }

?>