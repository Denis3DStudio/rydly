<?php

    namespace Controller\Api_History;

    use Base_Controller;
    use Base_Functions;

    class Methods extends Base_Controller {

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }
        #endregion

        #region Public Methods

            public function index() {
                
                // Init base logs folder
                $base_logs_folder = OFF_ROOT . "/contents/logs/api/" . date("Y-m-d") . "/history.json";

                // Check if the file exists
                if (!file_exists($base_logs_folder)) {
                    $this->Response->Success([]);
                    return;
                }

                // Get file contents
                $contents = file_get_contents($base_logs_folder);

                $this->Response->Success(Base_Functions::IsNullOrEmpty($contents) ? [] : json_decode($contents));
            }

        #endregion

        #region Private Methods
        
        #endregion

    }

?>