<?php 

    class Base_Controller extends Response {

        #region Properties
        
            public $Logged;
        
            public $__linq;
            public $__opHelper;

            private $__external_props;

        #endregion

        #region Constructors-Destructors

            public function __construct() { 
                parent::__construct();

                // Init
                $this->__external_props = new stdClass();
            }
            public function __destruct() { 
            }

        #endregion

        #region Set/Get

            public function __set($name, $value) {
                $this->__external_props->{$name} = $value;
            }
            public function __get($name) {

                // Check if already exists
                if(property_exists($this->__external_props, $name))
                    return $this->__external_props->{$name};

                // Build path
                $path = ACTIVE_FULL_PATH . "/controllers/$name.php";

                // Get instance
                $instance = Base_Functions::IncludeExternalMethods($path);

                // Set base methods
                $this->initMethodBaseProperties($instance);

                // Set to external props
                $this->__external_props->{$name} = $instance;

                return $instance;
            }

        #endregion

        #region Private Methods
        
            private function initMethodBaseProperties(&$instance) {

                if(Base_Functions::IsNullOrEmpty($instance))
                    return;

                // Build define name
                $session_name = Session_Name_Current();

                // Check if already exists
                $session = Session_Get($session_name);

                // Set logged
                $instance->Logged = Base_Functions::IsNullOrEmpty($session) ? null : $session->getAttributes();

                // Set response
                $instance->Response = $this->Response;

                // Utils
                $instance->__linq = new Base_LINQHelper();
                $instance->__opHelper = new Base_OperationsHelper();
                $instance->__opHelper->__setCreator(!Base_Functions::IsNullOrEmpty($instance->Logged) ? $instance->Logged->IdAccount : null);

            }

        #endregion

    }