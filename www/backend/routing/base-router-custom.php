<?php

    class Base_Custom_Router {

        #region Constructors-Destructors

            public function __construct() {
            }
            public function __destruct() {}
            
        #endregion

        #region Main Methods

            public function BeforeRouterInit() {}

            public function SessionLoaded($session) {}

            public function Unauthorized() {

                header("Location: " . ACTIVE_PATH . "/login");
                exit;

            }

            public function AccountLogged() {}

            public function AccountAnonymous() {}

            public function AfterSetLogged() {}

            public function BeforeRenderPage() {}

        #endregion

        #region Custom Methods

        #endregion

    }

?>