<?php

    namespace Backend\Chunk;

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
            public function getFolderPath($chunksCode) {

                // Return the folder path
                return $this->createFolderPath($chunksCode, false);
            }

            // Post
            public function upload($chunksCode, $fileName) {

                // Get the files
                $files = $_FILES;

                // Check that the file is not empty
                if (count($files) > 0) {

                    $folder_path = $this->createFolderPath($chunksCode);

                    // Append the file to the chunk
                    file_put_contents(OFF_ROOT . $folder_path . $fileName, file_get_contents($files['Files']['tmp_name'][0]), FILE_APPEND);

                    return $this->Success();
                }

                return $this->Not_Found();
            }

            // Delete
            public function deleteAll($chunksCode) {

                $folder_path = OFF_ROOT . "/contents/chunk_upload/$chunksCode/";
                Base_Functions::deleteFiles($folder_path);
            }
            public function delete($chunksCode, $fileName) {

                // Get the folder path
                $folder_path = OFF_ROOT . $this->createFolderPath($chunksCode, false);
                $file_path = $folder_path . $fileName;

                // Check if the folder exists
                if (file_exists($file_path))
                    // Delete the folder
                    unlink($file_path);

                // Check if the folder is empty
                if (is_dir($folder_path) && count(scandir($folder_path)) == 2)
                    rmdir($folder_path);

                return $this->Success();
            }

        #endregion

        #region Private Methods

            private function createFolderPath($chunksCode, $createIfNotExists = true) {

                // Create the folder path
                $folder_path = "/contents/chunk_upload/$chunksCode/";

                // Create path if not exists
                if (!file_exists(OFF_ROOT . $folder_path) && $createIfNotExists)
                    mkdir(OFF_ROOT . $folder_path, 0755, true);

                return $folder_path;
            }

        #endregion
    }