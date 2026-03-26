<?php 

/* ADVICES
    Se qualche campo non viene aggiunto o salvato, controlla che nell'oggetto che passi si chiami come la colonna sul DB (è SENSITIVE)
*/

class Base_OperationsHelper {

    #region Properties
        private $__db;
        private $__linq;
        private $__idCreator;

        private $__obj;
        private $__table;
        private $__where;
    #endregion

    #region Constructors-Destructors
        public function __construct() {
            $this->__db = new Base_PDOHelper();
            $this->__linq = new Base_LINQHelper();

            $this->__obj = new stdClass();
            $this->__table = "";
            $this->__where = "";
        }
        public function __destruct() {   
        }

        public function __setCreator($id) {
            $this->__idCreator = $id;
        }
    #endregion

    #region Setters
        public function object($element) {
            if(!empty($element) && $element != new stdClass() && $element != "" && $element != null) {
                if(is_string($element)) {
                    $tmp = $element;
                    $element = new stdClass();
                    $element->ColumnName = $tmp;
                }

                if(!property_exists($element, "IdCreator") && $this->__idCreator != null && $this->__idCreator != "")
                    $element->IdCreator = is_numeric($this->__idCreator) ? $this->__idCreator : null;

                $this->__obj = $element;
            } else {
                echo "Object empty";
                throw new Exception("Object empty", 1);
            }

            return $this;
        }
        public function table($element) {
            if($element != "") {
                $this->__table = $element;
            } else {
                echo "Table empty";
                throw new Exception("Table empty", 1);
            }

            return $this;
        }
        public function where($element) {
            if($element != "") {
                $this->__where = $element;
            } else {
                echo "Where empty";
                throw new Exception("Where empty", 1);
            }

            return $this;
        }
    #endregion

    #region Insert
        public function insert($do_not_check_insert = false) {
            if($this->everythingIsFine()) {
                $describe = $this->describeTable($this->__table);
                if($describe == null) {
                    echo "Errors with the table ".$this->__table;
                } else {

                    $this->__obj->IdModifier = is_numeric($this->__idCreator) ? $this->__idCreator : null;
                    $this->__obj->UpdateDate = date("Y-m-d H:i:s");

                    $usefulFields = array();
                    foreach ($this->__obj as $key => $obj) {
                        if(in_array($key, $describe)) {
                            array_push($usefulFields, $key);
                        }
                    }

                    if(count($usefulFields) > 0) {
                        $sql = "INSERT INTO ".$this->__table." (";

                        $first = true;
                        foreach ($usefulFields as $key => $field) {
                            $sql .= ($first) ? $field : ", $field";
                            $first = false;
                        }

                        $sql .= ") VALUES (";

                        $first = true;
                        foreach ($usefulFields as $key => $field) {
                            $sql .= ($first) ? ":$field" : ", :$field";
                            $first = false;
                        }

                        $sql .= ")";

                        $this->__db->setQuery($sql);

                        foreach ($usefulFields as $key => $field) {
                            $this->__db->setParameter(":$field", $this->__obj->$field);
                        }
                        $this->__db->execute();

                        return $this->__db->lastInsertId();
                    } else {
                        throw new Exception("No matches between the db table and your object founded", 1);
                        
                        return null;
                    }
                }
            }
        }
        public function insertIncrement() {
            if($this->everythingIsFine()) {
                $describe = $this->describeTable($this->__table);
                if($describe == null) {
                    echo "Errors with the table ".$this->__table;
                } else {

                    // If more than one field, the the normal insert
                    if(count((array)$this->__obj) == 2 && (in_array("IdCreator", $describe) || in_array("id_creator", $describe))) {
                        unset($this->__obj->ColumnName);
                        return $this->insert();
                    }
                    

                    if(in_array($this->__obj->ColumnName, $describe)) {
                        $this->__db->setQuery("INSERT INTO ".$this->__table." (".$this->__obj->ColumnName.") VALUES (null)")
                                   ->execute();
              
                        return $this->__db->lastInsertId();
                    } else {
                        throw new Exception("No matches between the db table and your object founded", 1);
                        
                        return null;
                    }

                }
                
            }
        }
        public function insertMassive($fields, $values) {
            if($this->__table != "" && $values != "") {
                
                $sql = "INSERT INTO ".$this->__table;

                $sql .= $fields;

                $sql .= " VALUES ";

                $sql .= $values;

                $this->__db->setQuery($sql)
                            ->execute();

                return $this->__db->lastInsertId();      
            }
        }
    #endregion

    #region Update
        public function update($fastWhere = "") {
            if($this->everythingIsFine(($fastWhere == ""))) {
                $describe = $this->describeTable($this->__table);
                if($describe == null) {
                    echo "Errors with the table ".$this->__table;
                } else {

                    $this->__obj->IdModifier = is_numeric($this->__idCreator) ? $this->__idCreator : null;
                    $this->__obj->UpdateDate = date("Y-m-d H:i:s");

                    $usefulFields = array();
                    $updateFields = array();
                    foreach ($this->__obj as $key => $obj) {
                        if(in_array($key, $describe)) {
                            array_push($usefulFields, $key);

                            if($this->__where == "" || $key != $this->__where)
                                array_push($updateFields, $key);
                        }
                    }

                    // Remove IdCreator
                    if (($key = array_search("IdCreator", $usefulFields)) !== false) {
                        if($usefulFields[$key] == null || $usefulFields[$key] == "") {
                            unset($usefulFields[$key]);
                            unset($this->__obj->IdCreator);
                        }
                    }

                    if (($key = array_search("IdCreator", $updateFields)) !== false) {
                        if($updateFields[$key] == null || $updateFields[$key] == "")
                            unset($updateFields[$key]);
                    }

                    if(count($usefulFields) > 0) {
                        $sql = "UPDATE ".$this->__table." SET ";

                        $first = true;
                        foreach ($updateFields as $field) {
                            if($first) {
                                $sql .= "$field = :$field";
                            } else {
                                $sql .= ", $field = :$field";
                            }
                            $first = false;
                        }

                        if($fastWhere == "") {
                            if($this->__where != "") $sql .= " WHERE ".$this->__where." = :".$this->__where;
                        } else {
                            $sql .= " WHERE $fastWhere";
                        }
                        
                        $this->__db->setQuery($sql);

                        foreach ($usefulFields as $field) {
                            $this->__db->setParameter(":$field", $this->__obj->$field);
                        }
                        $this->__db->execute();
                    } else {
                        throw new Exception("No matches between the db table and your object founded", 1);
                        
                        return null;
                    }
                }
            }
        }
    #endregion

    #region Insert/Update
        public function insert_update() {
            if($this->everythingIsFine()) {
                $describe = $this->describeTable($this->__table);
                if($describe == null) {
                    echo "Errors with the table ".$this->__table;
                } else {
                    
                    $usefulFields = array();
                    foreach ($this->__obj as $key => $obj) {
                        if(in_array($key, $describe)) {
                            array_push($usefulFields, $key);
                        }
                    }

                    if(count($usefulFields) > 0) {
                        $sql = "INSERT INTO ".$this->__table." (";

                        $first = true;
                        foreach ($usefulFields as $key => $field) {
                            $sql .= ($first) ? $field : ", $field";
                            $first = false;
                        }

                        $sql .= ") VALUES (";

                        $first = true;
                        foreach ($usefulFields as $key => $field) {
                            $sql .= ($first) ? ":$field" : ", :$field";
                            $first = false;
                        }

                        $sql .= ")";

                        $sql .= " ON DUPLICATE KEY UPDATE ";

                        $first = true;
                        foreach ($usefulFields as $key => $field) {
                            $sql .= ($first) ? "$field = :$field" : ", $field = :$field";
                            $first = false;
                        }

                        $this->__db->setQuery($sql);

                        foreach ($usefulFields as $key => $field) {
                            $this->__db->setParameter(":$field", $this->__obj->$field);
                        }
                        $this->__db->execute();

                        return $this->__db->lastInsertId();
                    } else {
                        throw new Exception("No matches between the db table and your object founded", 1);
                        
                        return null;
                    }
                }
            }
        }
    #endregion

    #region Delete
        public function delete() {
            if($this->everythingIsFine()) {
                $describe = $this->describeTable($this->__table);
                if($describe == null) {
                    echo "Errors with the table ".$this->__table;
                } else {

                    $usefulFields = array();
                    foreach ($this->__obj as $key => $obj) {
                        if(in_array($key, $describe)) {
                            array_push($usefulFields, $key);
                        }
                    }

                    if(count($usefulFields) > 0) {
                        $sql = "DELETE FROM ".$this->__table;

                        $sql .= " WHERE ".$this->__where." = :".$this->__where;
                        
                        $this->__db->setQuery($sql)
                                ->setParameter(":".$this->__where, $this->__obj->{$this->__where})
                                ->execute();
                    } else {
                        throw new Exception("No matches between the db table and your object founded", 1);
                        return null;
                    }
                }
            }
        }
        public function massiveDelete($IdName, $IdValue, $tables) {
            foreach ($tables as $key => $table) {
                $sql = "DELETE FROM ".$table;
                $sql .= " WHERE ".$IdName." = :".$IdName;
                
                $this->__db->setQuery($sql)
                        ->setParameter(":".$IdName, $IdValue)
                        ->execute();
            }
        }
        public function deleteIn($IdName, $array) {
            if($this->__table != null && $this->__table != "" && count($array) > 0) {

                $el = implode("','", $array);

                $sql = "DELETE FROM ".$this->__table;
                $sql .= " WHERE ".$IdName." IN ('".$el."')";
                
                $this->__db->setQuery($sql)
                    ->execute();
            }
        }
        public function truncate() {
            if($this->__table != null) {
                $sql = "TRUNCATE TABLE ".$this->__table;
                
                $this->__db->setQuery($sql)
                    ->execute();
            }
        }
    #endregion

    #region Private Methods
        private function describeTable($tableName) {
            $query = "DESCRIBE $tableName";

            $columns = $this->__db->setQuery($query)
                ->execute(true)->getResults();

            if($columns != null) {
                return $this->__linq->select("Field")->from($columns)->getResults();
            } else {
                return null;
            }
        }
        private function everythingIsFine($update = false) {
            $returnValue = true;

            if(empty($this->__obj) || $this->__obj == new stdClass() || $this->__obj == "" || $this->__obj == null) {
                $returnValue = false;
                echo "Object empty<br>";
                throw new Exception("Object empty", 1);
            }

            if($this->__table == "") {
                $returnValue = false;
                echo "Table empty<br>";
                throw new Exception("Table empty", 1);
            }

            if($update) {
                if($this->__where == "") {
                    $returnValue = false;
                    echo "Where empty<br>";
                    throw new Exception("Where empty", 1);
                }
            }

            return $returnValue;
        }
    #endregion 

}