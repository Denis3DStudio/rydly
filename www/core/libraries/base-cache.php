<?php

class Base_Cache {

    #region Public Methods

        public static function set($key, $value, $expirationInMinutes = 14400) {

            try {

                // Create file path
                $cacheFile = self::createFilePath($key);

                // Save content
                file_put_contents($cacheFile, self::buildFileCacheContent($value, $expirationInMinutes));

                return true;

            }
            catch (Exception $e) {
            }

            return false;
        }
        public static function get($key) {

            try {

                // Get file path
                $cacheFile = self::getFilePath($key);

                if($cacheFile !== false) {

                    // Get file
                    $cache = self::getCacheFileContent($cacheFile);

                    // Check if expired
                    if($cache->Expired) {

                        // Remove file
                        unlink($cacheFile);

                        return false;
                    }

                    // Return
                    return $cache->Content;

                }
            }
            catch (Exception $e) {
            }

            return false;
        }
        public static function delete($key) {

            try {

                // Get file path
                $cacheFile = self::createFilePath($key);

                // Check if exists and delete
                if (file_exists($cacheFile))
                    unlink($cacheFile);

            }
            catch (Exception $e) {
            }
        }
        public static function clear() {

            try {

                self::clearCaches();
                
            }
            catch (Exception $e) {
            }

        }
        public static function clearExpiredCache() {
            self::clearExpired();
        }

    #endregion

    #region Private Methods

        private static function createFilePath($key) {
            // Check subfolder
            list($key, $subfolder) = self::checkKeySubfolders($key);

            // Build filename
            return self::getBasePath($subfolder) . "$key.cache";
        }

        private static function buildFileCacheContent($value, $expiration) {

            // Calculate expiration time
            $expiration = time() + ($expiration * 60);

            // Check if value is string
            $value = json_encode($value);

            // Build body
            return $expiration . "|" . $value;
        }

        private static function getFilePath($key) {
            $response = false;

            // Get file
            $cacheFile = self::createFilePath($key);

            // Check if exists
            if (file_exists($cacheFile))
                $response = $cacheFile;

            return $response;
        }

        private static function getBasePath($subfolder = '') {
            $path = OFF_ROOT . "/contents/caches/$subfolder/";

            // Check if folder exists
            if(!file_exists($path))
                mkdir($path, 0777, true);

            return str_replace("//", "/", $path);
        }

        private static function clearExpired($base_path = null) {

            if ($base_path == null)
                $base_path = self::getBasePath();

            // Get all cache file
            $files = glob("$base_path*");

            foreach ($files as $file) {

                // Check if the file is a cache or a folder
                if (Base_Functions::HasSubstring($file, ".cache") == false) {

                    // Check if the folder is empty
                    if(count(glob("$file")) == 0)
                        // Delete folder
                        rmdir($file);
                    else
                        // Clear the folder
                        self::clearExpired("$file/");
                }

                // Check if expired
                else if(self::getCacheFileContent($file, true)->Expired)
                    unlink($file);

            }
        }

        private static function clearCaches() {

            // Get base path
            $base_path = self::getBasePath();

            // Delete all files
            Base_Functions::deleteFiles($base_path);

        }

        private static function checkKeySubfolders($key) {
            $subfolder = "";

            // Check if has a subfolder
            if(strpos($key, "/") !== false) {
                $exp = explode("/", $key);

                // Get filename
                $key = array_pop($exp);

                // Get subfolder
                $subfolder = implode("/", $exp);
            }

            return array($key, $subfolder);

        }

        private static function getCacheFileContent($file, $only_expiration = false) {
            $response = new stdClass();
            $response->Expired = false;
            $response->Content = null;

            // Check file size
            if(filesize($file) == 0) {

                // Set as expired to remove
                $response->Expired = true;

                return $response;
            }

            // Get file content and decode
            $content = file_get_contents($file);

            // Get time default length
            $length = strlen(time() . "|");

            // Get expiration
            $expiration = substr($content, 0, $length);

            // Remove |
            $expiration = rtrim($expiration, "|");

            // Check if expired
            if($expiration < time()) {

                // Set expired
                $response->Expired = true;

                return $response;
            }

            // Check if only expiration
            if($only_expiration) return $response;

            // Get body
            $content = substr($content, $length);

            // Check if is a json
            $content = json_decode($content);

            // Set content
            $response->Content = $content;

            // Return
            return $response;
        }

    #endregion

}