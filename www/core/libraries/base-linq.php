<?php

class Base_LINQHelper {

    #region Private variables
        private $__select;
        private $__distinct;
        private $__from;
        private $__where;
        private $__results;
        private $__limit;
        private $__orderBy;
        private $__orderType;

        private $__hostname;
        private $__username;
        private $__password;
        private $__database;

        private $__selectDB;
        private $__whereDB;
        private $__dbTableName;
        private $__sql;
        private $__bindParams;

        private $__firstResult = false;
    #endregion

    #region Constructors-Destructors
        public function __construct() {
            $this->cleanParameters();

            // Clear
            if(defined("DATABASE_HOST") && defined("DATABASE_USER") && defined("DATABASE_PASSWORD") && defined("DATABASE_NAME"))
                $this->dbParams(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME);
            
        }
        public function __destruct() {   
        }
    #endregion

    #region DB
        private function getFromDB() {
            $tableName = $this->__dbTableName;

            $sql = "SELECT ";

            $sql .= ($this->__selectDB != "") ? $this->__selectDB : "*";
            
            $sql .= " FROM `$tableName`";

            $this->__sql = $sql;

            if($this->__whereDB != "") {
                $this->__sql = $sql . " WHERE " . $this->__whereDB;
            }

            $this->executeDbQuery();

            return $this;
        }
        private function freeQuery() {
            $this->executeDbQuery();
            
            return $this;
        }

        private function formatQueryConditions($conditions) {
            // Replace line break with whitespace
            $sql = trim(preg_replace('/\s+/', ' ', $conditions));

            // Add whitespace at the begin and end so if the query ends with 1, the last operator will works
            $sql = " $sql ";

            $finalQuery = "";
            $bindParams = array();

            $operators = array("=", "!=", "<>", "LIKE");

            // Divide string by chars
            $splitted_string = str_split($sql);

            $diff = 0;

            $afterOperator = false;
            $wordBeginIndex = false;
            
            $hasBrackets = false;
            $openingOperator = false;
            $closingOperator = array();

            $stopSearching = true;

            foreach ($splitted_string as $key => $char) {
                $word = "";
                $notAddChar = false;

                // Set hasBrackets to false everytime the word is ended or not set
                if($wordBeginIndex == false) $hasBrackets = false;

                // Is a whitespace and the closing operator is NOT the whitespace
                if(ctype_space($char) && !in_array(" ", $closingOperator)) {

                    // Already an active word, get the word using the last index
                    if($wordBeginIndex != false) {
                        $word = substr($sql, $wordBeginIndex, $key - $wordBeginIndex);

                        if(!$afterOperator) $wordBeginIndex = false;
                    }

                } else {

                    if($afterOperator) {
                        // Define opening sign
                        if(is_bool($openingOperator) && $openingOperator == false) {

                            $openingOperator = $char;
                            $closingOperator = array($char);
                            
                            // Check if char is ' or " to get the next char - otherwise set the closing operator as whitespace or )
                            if($char != "'" && $char != '"') {
                                $closingOperator = array(" ", ")");
                                $wordBeginIndex = $key;
                            } else {
                                $hasBrackets = true;
                                $wordBeginIndex = $key + 1;
                            }

                        } else {

                            if(in_array($char, $closingOperator)) {
                                $word = substr($sql, $wordBeginIndex, $key - $wordBeginIndex);

                                $add = true;

                                // If the separator is the whitespace or the )
                                if(in_array(" ", $closingOperator) && in_array(")", $closingOperator)) {

                                    /**
                                     * NOT ADD WHEN:
                                     * - the condition is like CURRENT_DATE => 'CURRENT_DATE' is allowed
                                     * - the word contains a dot and has not brackets. This is to avoid to replace the table.column with the bind during a ON condition in a JOIN
                                     * - first char is a (
                                     * - the condition is like NOW => 'NOW' is allowed
                                     */
                                    if(strpos(strtoupper($word), "CURRENT") !== false || (strpos(strtoupper($word), ".") != false && $hasBrackets == false) || $word[0] == "(" || strpos(strtoupper($word), "NOW") !== false)
                                        $add = false;                                                                      
                                }
                                
                                // Add to the bind
                                if($add) {
                                    $keyword = ":Bind" . (count($bindParams) + 1);

                                    // Add bind
                                    $bind = new stdClass();
                                    $bind->Key = $keyword;
                                    $bind->Value = $word;
                                    array_push($bindParams, $bind);

                                    $addIndex = 0;

                                    if($char == "'" || $char == '"') {
                                        $addIndex = 1;
                                        $notAddChar = true;
                                    }

                                    // Replace value with :BindX
                                    $finalQuery = substr_replace($finalQuery, $keyword, ($key - strlen($word)) - $addIndex + $diff);

                                    // Calculate the difference for the next replacemente
                                    $diff += strlen($keyword) - strlen($word) - $addIndex - $addIndex;
                                }

                                $openingOperator = false;
                                $closingOperator = array();

                                $afterOperator = false;
                            }

                        }                        
                    }
                    else {

                        // There isn't an active word
                        if($wordBeginIndex == false) {
                            $wordBeginIndex = $key;

                            // If next is whitespace, end the word
                            if(ctype_space($splitted_string[$key+1]) || in_array($char, $operators)) {
                                $word = $char;
                                $wordBeginIndex = false;
                            }
                        }

                    }
                }

                // Check if to add to the final query
                if(!$notAddChar)
                    $finalQuery .= $char;


                if(!is_bool($wordBeginIndex) && in_array(strtoupper($char), $operators) && !$afterOperator) {
                    $word = $char;
                    $wordBeginIndex = false;
                }


                if($word != "" && !$afterOperator) {
                    if(strtoupper($word) == "SELECT")
                        $stopSearching = true;                    
                    elseif(strtoupper($word) == "FROM")
                        $stopSearching = false;

                    if(!$stopSearching && in_array(strtoupper($word), $operators))
                        $afterOperator = true;
                }
            }

            $this->__bindParams = $bindParams;

            return $finalQuery;
        }

        private function executeDbQuery() {
            $this->__sql = $this->formatQueryConditions($this->__sql);

            // Check LIMIT
            if($this->__limit != "" && $this->__limit > 0)
                $this->__sql = " LIMIT " . $this->__limit;

            $pdoHelper = new Base_PDOHelper($this->__hostname, $this->__username, $this->__password, $this->__database);
            $stmt = $pdoHelper->setQuery($this->__sql);

            foreach ($this->__bindParams as $param)
                $stmt->setParameter($param->Key, $param->Value);
            
            $results = $stmt->execute(true)->getResults();

            if($this->__where == "" && count($this->__select) == 0)
                $this->__results = $results;
            else
                $this->__from = $results;
            
        }
    #endregion

    #region Setter Methods
        public function dbParams($hostname, $username, $password, $database) {

            $this->__hostname = $hostname;
            $this->__username = $username;
            $this->__password = $password;
            $this->__database = $database;

            return $this;

        }

        public function select($selectElement) {
            if($selectElement != "" && $selectElement != null) {
                $explode = explode(",", $selectElement);

                foreach ($explode as $key => $exp) {
                    $element = str_replace(" ", "", $exp);
                    array_push($this->__select, $element);
                }
            } else {
                throw new Exception("Select empty", 1);
            }

            return $this;
        }
        public function selectDB($selectDbElement) {
            if($selectDbElement != "") {
                $this->__selectDB = strip_tags($selectDbElement);
            } else {
                throw new Exception("Select DB empty", 1);
            }

            return $this;
        }
        public function distinct($selectElement = "") {
            if($selectElement != "") {
                $this->__distinct = true;
                $this->select($selectElement);
            } else {
                throw new Exception("Distinct empty", 1);
            }

            return $this;
        }
        public function from($fromElement) {
            if($fromElement != "") {
                if(!is_array($fromElement)) {
                    // $arr = array();
                    // array_push($arr, $fromElement);
                    
                    $fromElement = (array)$fromElement;
                }
                $this->__from = $fromElement;
            } else {
                throw new Exception("From empty", 1);
            }

            return $this;
        }
        public function fromDB($tableName) {
            if($tableName != "") {
                $this->__dbTableName = $tableName;
            } else {
                throw new Exception("From empty", 1);
            }

            return $this;
        }
        public function queryDB($sql) {
            if($sql != "") {
                $this->__sql = $sql;
            } else {
                throw new Exception("SQL empty", 1);
            }

            return $this;
        }
        public function getAutoIncrement($tableName) {
            if($tableName != "") {
                $this->__sql = "SELECT AUTO_INCREMENT
                                FROM INFORMATION_SCHEMA.TABLES
                                WHERE TABLE_NAME = '$tableName'
                                AND TABLE_SCHEMA = '$this->__database'";
            } else {
                throw new Exception("SQL empty", 1);
            }

            return $this;
        }
        public function limit($limitElement) {
            if($limitElement != "") {
                $this->__limit = $limitElement;
            } else {
                throw new Exception("Limit empty", 1);
            }

            return $this;
        }
        public function orderBy($orderByElement, $type = "ASC") {
            if($orderByElement != "") {
                $this->__orderBy = $orderByElement;
                $this->__orderType = $type;
            } else {
                throw new Exception("OrderBy empty", 1);
            }

            return $this;
        }
        public function where($where) {
            if($where != "") {
                if (strpos($where, '=>') == false)
                    throw new Exception("Syntax error (es. x => x.Name == 'Andrea' )", 1);
                    
                $exp = explode("=>", $where);

                // Get used variable name
                $var = trim($exp[0]);

                // Replace used variable with mine
                $query = str_replace($var.".", "\$element->", $exp[1]);

                $this->__where = $query;
            } else {
                throw new Exception("Where Clause empty", 1);
            }

            return $this;
        }
        public function whereDB($whereDB) {
            if($this->__dbTableName != "") {
                if($whereDB != "") {
                    $whereDB = str_replace("&&", "AND", $whereDB);
                    $whereDB = str_replace("||", "OR", $whereDB);
                    $whereDB = str_replace("==", "=", $whereDB);

                    $this->__whereDB = strip_tags($whereDB);
                } else {
                    throw new Exception("Where Clause empty", 1);
                }
            } else {
                throw new Exception("FromDB Clause empty", 1);
            }

            return $this;
        }
    #endregion

    #region Execute The Search
        public function executeFind() {
            if($this->__where != "" || count($this->__select) > 0) {
                foreach ($this->__from as $element) {
                    $this->__where = ($this->__where != "" && $this->__where != null) ? $this->__where : "true";
                    $oper = "return ".$this->__where.";";
                    if(eval($oper)) {
                        $this->selectValues($element);

                        if($this->__firstResult) break;
                    }
                }
            } else {
                $this->__results = $this->__from;
            }
        }
    #endregion

    #region Select Values
        private function selectValues($element) {
            $obj = new stdClass();

            if($this->__select != null && count($this->__select) > 0) {
                if(count($this->__select) > 1) {
                    foreach ($this->__select as $select) {
                        if(property_exists($element, $select)) {
                            $obj->{$select} = $element->{$select};
                        } else {
                            throw new Exception("Property '".$select."' not exists", 1);
                        }
                    }
                } else {
                    if(property_exists($element, $this->__select[0])) {
                        $obj = $element->{$this->__select[0]};
                    } else {
                        throw new Exception("Property '".$this->__select[0]."' not exists", 1);
                    }
                }
            } else {
                $obj = $element;
            }

            array_push($this->__results, $obj);
        }
    #endregion

    #region Order
        /** Returns an array of objects that has key an object property
         * @param array $object Array of objects to reorder
         * @param string $mainKey Property used for reorder
         * @param bool $array Return always an array reordered
         * @return object $ret Reordered array of objects
         */
        public function reorder($objects, $mainKey, $array = false) {
            $ret = new stdClass();

            // transform into array
            if(!is_array($objects))
                $objects = array($objects);

            foreach ($objects as $obj) {

                // Check that the obj is not null
                if (!Base_Functions::IsNullOrEmpty($obj)) {

                    // check if $mainKey is a property of $obj 
                    if(property_exists($obj, $mainKey) && $obj->{$mainKey} != null) {
    
                        // add new value to response
                        if(!property_exists($ret, $obj->{$mainKey})) {
    
                            // return always array
                            if ($array) {
    
                                $ret->{$obj->{$mainKey}} = array();
                                array_push($ret->{$obj->{$mainKey}}, $obj);
                            }
                            // create object for normal response
                            else
                                $ret->{$obj->{$mainKey}} = $obj;
    
                        }
                        // add a value to an existing property of response
                        else {
    
                            // transform into array
                            if(!is_array($ret->{$obj->{$mainKey}}))
                                $ret->{$obj->{$mainKey}} = array($ret->{$obj->{$mainKey}});
    
                            array_push($ret->{$obj->{$mainKey}}, $obj);
                        }
                    }
                }
            }

            return $ret;
        }

        // Annulla l'ordinamento creato con la funzione reorder
        public function back_reorder($objects) {
            $ret = array();
            foreach ($objects as $key => $obj) {
                if(is_array($obj)) {
                    foreach ($obj as $key1 => $o) {
                        array_push($ret, $o);
                    }
                } else {
                    array_push($ret, $obj);
                }
            }

            return $ret;
        }
    #endregion
    
    #region Results
        public function getFirstOrDefault() {
            $this->__firstResult = true;

            if($this->__results == null)
                $results = $this->getResults();

            return ($results != null && count($results) > 0) ? $results[0] : null;
            
        }
        public function getResults() {

            // DB
            if($this->__sql != "" || $this->__dbTableName != "") {

                if($this->__sql != "") {

                    $this->freeQuery();
                    $this->__sql = "";

                }
                else {

                    $this->getFromDB();
                    $this->__dbTableName == "";

                }
            }

            // Array of objects
            elseif($this->__results == null) {

                $this->executeFind();

                if($this->__distinct && count($this->__select) <= 1)
                    $this->__results = array_unique($this->__results);

                if($this->__limit != null && $this->__limit > 0)
                    $this->__results = array_slice($this->__results, 0, $this->__limit);
                
            }

            if($this->__orderBy != null)
                usort($this->__results, array($this, "orderResults"));

            $results = $this->__results;
            $this->cleanParameters();

            return $results;
        }
    #endregion

    #region Private Methods
        function orderResults($a, $b) {
            $first = $a->{$this->__orderBy};
            $second = $b->{$this->__orderBy};

            return ($this->__orderType == "ASC") ? strnatcmp($first, $second) : strnatcmp($second, $first);
        }

        private function cleanParameters() {
            $this->__select = array();
            $this->__where = "";
            $this->__whereDB = "";
            $this->__from = array();
            $this->__limit = null;
            $this->__orderBy = null;
            $this->__orderType = "ASC";
            $this->__selectDB = "";
            $this->__bindParams = array();
            $this->__dbTableName = "";
            $this->__sql = "";

            $this->__firstResult = false;
            $this->__results = array();
        }
    #endregion
}