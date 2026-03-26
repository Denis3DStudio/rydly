<?php

    namespace Controller\Translation;

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

            public function export($response) {

                // Check if login has been successful
                if(!$this->Response->Success)
                    return;

                // Build path
                $path = OFF_ROOT . "/contents/translations_export/$response";

                // Check if file exists
                if(!file_exists($path))
                    return $this->Not_Found();

                // Download
                header("Cache-Control: public"); // needed for internet explorer
                header("Content-Type: " . Base_Functions::Ext2Mime($path));
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length:" . filesize($path));
                header("Content-Disposition: attachment; filename=$response");

                // Read the file
                ob_clean();
                readfile($path);

                // Remove file
                unlink($path);
                exit;
            }

        #endregion

        #region Private Methods
        
        #endregion

    }

?>