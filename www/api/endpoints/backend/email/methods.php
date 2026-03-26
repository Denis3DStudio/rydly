<?php

    namespace Backend\Email;

    use stdClass;
    use Base_Methods;
    use Base_Functions;

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
            public function get($idEmail) {

                // Get the email
                $email = $this->__linq->fromDB("emails")->whereDB("IdEmail = $idEmail")->getFirstOrDefault();

                // Check if the email exists
                if (!Base_Functions::IsNullOrEmpty($email))
                    return $this->Success($email);

                return $this->Not_Found();
            }
            public function getAll() {

                // Select all emails
                $emails = $this->__linq->fromDB("emails")->whereDB("IsDeleted = 0")->getResults();

                return $this->Success($emails);
            }

        #endregion

    }

?>