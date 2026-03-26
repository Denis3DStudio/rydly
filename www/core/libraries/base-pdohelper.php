<?php

class Base_PDOHelper {
    
    #region Private variables

        private $__db;
        private $__stmt;
        private $__results;

    #endregion

    #region Constructors-Destructors
        
        public function __construct($hostname = null, $username = null, $password = null, $database = null) {
            if(Base_Functions::IsNullOrEmpty($hostname, $username, $database)) {

                // Set default
                if(defined("DATABASE_HOST") && defined("DATABASE_USER") && defined("DATABASE_PASSWORD") && defined("DATABASE_NAME")) {
                    $hostname = DATABASE_HOST;
                    $username = DATABASE_USER;
                    $password = DATABASE_PASSWORD;
                    $database = DATABASE_NAME;
                }
                else
                    return;

                // Check again
                if(Base_Functions::IsNullOrEmpty($hostname, $username, $database))
                    return;
            }

            try {
                // Set connection string
                $connectionString = 'mysql:host=' . $hostname . ';dbname=' . $database . ';charset=utf8mb4;';
                
                // Connect to database
                $this->__db = new PDO($connectionString, $username, $password, array(PDO::ATTR_TIMEOUT => 10));
                $this->__db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            catch (PDOException $e) {
                $this->ErrorResponse($e->getMessage());
            }
        }
        public function __destruct() {}

    #endregion
    
    #region Public Methods

        public function setParameter($name, $value, $typeOf = PDO::PARAM_STR) {
            // Check if starts with :
            if ((substr($name, 0, strlen(":")) === ":") == false)
                $name = ':' . $name;

            // Bind value
            if ($this->__stmt != null)
                $this->__stmt->bindValue($name, $value, $typeOf);

            return $this;
        }
        public function setQuery($query) {
            // Check DB
            if ($this->__db == null)
                return $this;

            // Prepare statement
            $this->__stmt = $this->__db->prepare($query);

            return $this;
        }
        public function execute($returnAsObject = false) {
            // Check DB
            if ($this->__db == null)
                return $this;

            $results = null;

            $this->__stmt->execute();

            // Check operation type
            if(strpos(trim(strtoupper($this->__stmt->queryString)), "INSERT INTO") === false &&
                strpos(trim(strtoupper($this->__stmt->queryString)), "DELETE FROM") === false &&
                strpos(trim(strtoupper($this->__stmt->queryString)), "CREATE TABLE") === false &&
                strpos(trim(strtoupper($this->__stmt->queryString)), "ALTER TABLE") === false &&
                substr(trim(strtoupper($this->__stmt->queryString)), 0, 6) != "UPDATE") {

                // Return object
                if ($returnAsObject == true)
                    $results = $this->__stmt->fetchAll(PDO::FETCH_OBJ);
                
                // Return array
                else
                    $results = $this->__stmt->fetchAll();

            }

            // Set results
            $this->__results = $results;

            return $this;
        }
        public function lastInsertId() {
            return $this->__db->lastInsertId();
        }
        public function getResults() {
            return $this->__results;
        }

    #endregion

    #region Private Methods
        
        private function ErrorResponse($message = null) {
            $response = new stdClass();
            $response->Message = $message ?? "Connection to database failed";
            $response->Response = null;

            // Clear buffer
            if (ob_get_contents()) ob_clean();

            // Set status code
            http_response_code(500);

            // Cache and content-type
            header('Cache-Control: no-cache, must-revalidate');
            header('Content-type: application/json');
            
            // CORS Headers
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Max-Age: 1000");
            header("Access-Control-Expose-Headers: Refreshed-Jwt");
            header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, Authorization, Accept, Accept-Encoding");
            header("Access-Control-Allow-Methods: PUT, POST, GET, DELETE");

            // Show response
            echo json_encode($response);
            exit;

        }
    
    #endregion

}