<?php 

class Base_Session {

    private $__sessionName;
    private $__attributes;

    #region Constructors-Destructors
        public function __construct($name = null) {
            Session_Init();

            $this->__sessionName = $name;
            $this->__attributes = new stdClass();

            $this->initSession();
            $this->initAttributes();
        }
        public function __destruct() {
        }

        public function __get($name) {
            // Check if name is __attributes
            if($name == "__attributes")
                return $this->__attributes;

            return $this->__attributes->{$name};
        }
        public function __set($name, $value) {
            $this->__attributes->{$name} = $value;
        }
    #endregion

    #region Public Methods
        /**
         * Add/Update to the session
         */
        public function setAttribute($name, $value) {

            // Get value
            $value = (Base_Functions::IsJson($value)) ? json_decode($value) : $value;
            
            // Init
            if(is_object($value)) {
                $this->$name = new stdClass();
            }
            elseif(is_array($value)) {
                $this->$name = array();
            }
            
            // Set attribute
            $this->$name = $value;

            // Remove from session
            $this->removeAttribute($name);
            
            // Set in session
            $_SESSION[$this->__sessionName]->$name = $value;
        }

        /**
         * Remove to the session
         */
        public function removeAttribute($name) {
            if(isset($_SESSION[$this->__sessionName]->$name))
                unset($_SESSION[$this->__sessionName]->$name);
        }

        /**
         * Return public attributes
         */
        public function getAttributes($exclude_class_variables = false) {
            $response = new stdClass();
            
            foreach ($this->__attributes as $key => $value) {
                $response->{$key} = $value;
            }

            // Check if to not exclude session name
            if($exclude_class_variables == false)
                $response->__sessionName = $this->__sessionName;

            return $response;
        }
    #endregion

    #region Private Methods
        private function initSession() {
            if(!isset($_SESSION[$this->__sessionName]))
                $_SESSION[$this->__sessionName] = new stdClass();
        }
        private function initAttributes() {
            foreach ($_SESSION[$this->__sessionName] as $key => $attr)
                $this->$key = $attr;
        }
    #endregion
}

/**
 * Start session
 */
function Session_Init() {
    if (session_status() == PHP_SESSION_NONE || $_SESSION === null) {
        $session_id = Base_Auth::getSessionId();

        // Check again
        if (session_status() == PHP_SESSION_NONE || $_SESSION === null) {
            session_id($session_id);
            session_start();
        }
    }
}

/**
 * Get session by name
 * @param $name the session name
 */
function Session_Get($name) {
    Session_Init();

    $session = null;

    if (isset($_SESSION[$name]))
        $session = new Base_Session($name);

    return $session;
}
/**
 * Remove session by name
 * @param $name the session name
 */
function Session_Remove($name) {
    Session_Init();

    if(isset($_SESSION[$name]))
        unset($_SESSION[$name]);
}
/**
 * Get session name by ACTIVE_PATH
 */
function Session_Name_Current() {
    return constant(strtoupper(ltrim(ACTIVE_PATH, "/")) . "_SESSION");
}