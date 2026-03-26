<?php

    namespace Backend\Job;

    use Base_Automatic_Mail;
    use Base_File;
    use Base_Files;
    use Base_Files_Types;
    use Base_Functions;
    use Base_Methods;
    use Base_Mail;
    use Mails_Labels;

    class Methods extends Base_Methods {

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }      
        #endregion
        
        #region Public Methods

            #region Rows

                public function deleteInvalidRows() {

                    // Define the tables and their relations
                    $tablesRelations = [
                    ];

                    // Get all the tables that have IsValid and InsertDate columns
                    $sql = "SELECT DISTINCT TABLE_NAME 
                            FROM INFORMATION_SCHEMA.COLUMNS 
                            WHERE COLUMN_NAME = 'IsValid' AND TABLE_SCHEMA='".DATABASE_NAME."'";
                    $tablesWithIsValid = array_column($this->__linq->queryDB($sql)->getResults(), "TABLE_NAME");

                    $sql = "SELECT DISTINCT TABLE_NAME 
                            FROM INFORMATION_SCHEMA.COLUMNS 
                            WHERE COLUMN_NAME = 'InsertDate' AND TABLE_SCHEMA='".DATABASE_NAME."'";
                    $tablesWithInsertDate = array_column($this->__linq->queryDB($sql)->getResults(), "TABLE_NAME");

                    // Build the expiration date (7 days ago)
                    $expirationDate = date('Y-m-d', strtotime('-7 days'));

                    // Recursive function to delete rows and their relations
                    function deleteRecursive($pk, $rows, $relations, $tablesWithInsertDate, $expirationDate, $linq) {

                        // Check if the rows are empty
                        if (empty($relations)) 
                            return;

                        // Check if the rows are empty
                        foreach ($relations as $relation) {
                            // Set as object
                            $relation = (object)$relation;

                            // Check if the relation has a foreign key
                            $relatedTable = $relation->Table;
                            $innerKey = $relation->InnerKey ?? null;

                            // Check if the related table is valid
                            if (!$innerKey) 
                                continue;

                            // Get the ids
                            $ids = array_column($rows, $pk);

                            // If ids is empty, continue
                            if (empty($ids)) 
                                continue;

                            // Build the where clause 
                            $whereClause = "$innerKey IN (" . implode(',', $ids) . ")";

                            // If ContentName, add it to the where clause
                            if (property_exists($relation, 'ContentName'))
                                $whereClause .= " AND ContentName = '" . $relation->ContentName . "'";

                            // Get the child rows
                            $query = "SELECT * FROM $relatedTable WHERE $whereClause";
                            $childRows = $linq->queryDB($query)->getResults();

                            // Check if there are child rows
                            if (!empty($relation->RelatedTables))
                                deleteRecursive($relation->PrimaryKey, $childRows, $relation->RelatedTables, $tablesWithInsertDate, $expirationDate, $linq);

                            // Delete the child rows
                            $deleteQuery = "DELETE FROM $relatedTable WHERE $whereClause";
                            $linq->queryDB($deleteQuery)->getResults();
                        }
                    }

                    #region Main Tables

                        // Cycle through the main tables with IsValid
                        foreach ($tablesWithIsValid as $mainTable) {
                            $extraWhere = "";

                            // Check if the main table has InsertDate
                            if (in_array($mainTable, $tablesWithInsertDate))
                                $extraWhere = "AND InsertDate <= '$expirationDate'";

                            // Get the rows with IsValid = 0
                            $selectQuery = "SELECT * FROM $mainTable WHERE IsValid = 0 $extraWhere";
                            $rows = $this->__linq->queryDB($selectQuery)->getResults();

                            // Check if there are rows to delete
                            if (empty($rows)) 
                                continue;

                            // Check if the main table has relations
                            foreach ($tablesRelations as $relation) {

                                // Set as object
                                $relation = (object)$relation;

                                // Check if the relation is for the main table
                                if ($relation->MainTable === $mainTable) {
                                    deleteRecursive(
                                        $relation->PrimaryKey, 
                                        $rows, 
                                        $relation->RelatedTables ?? [], 
                                        $tablesWithInsertDate, 
                                        $expirationDate, 
                                        $this->__linq
                                    );
                                    break;
                                }
                            }

                            // Delete the main rows
                            $deleteMainQuery = "DELETE FROM $mainTable WHERE IsValid = 0 $extraWhere";
                            $this->__linq->queryDB($deleteMainQuery)->getResults();
                        }

                    #endregion

                    return $this->updateJob();
                }

            #endregion

            #region Files

                // Get
                public function deleteFilesAfter365days() {

                    $this->news->deleteFilesAfter365days();
                    $this->product->deleteFilesAfter365days();
                    return $this->updateJob();
                }

            #endregion

            #region Emails

                public function sendMails() {
                        
                    // Send all email
                    Base_Mail::getAndSendMails();
                    return $this->updateJob();
                }

            #endregion

        #endregion

        #region Private Methods

            private function updateJob($custom = null) {

                // Get caller
                $caller = debug_backtrace()[1];

                if(Base_Functions::IsNullOrEmpty($caller) || !isset($caller["function"]))
                    return true;

                // Set job name
                $name = $caller["function"];
                
                // Get job by name
                $job = $this->__linq->fromDB("jobs")->whereDB("Name = '$name'")->getFirstOrDefault();

                // Create object
                $obj = new stdClass();
                $obj->Name = $name;
                $obj->Custom = $custom;
                $obj->LastRun = date("Y-m-d H:i:s");

                // Insert
                if(Base_Functions::IsNullOrEmpty($job))
                    $this->__opHelper->object($obj)->table("jobs")->insert();

                // Update
                else {

                    // Set id job
                    $obj->IdJob = $job->IdJob;

                    // Move last run to penultimate
                    $obj->PenultimateRun = $job->LastRun;

                    // Update
                    $this->__opHelper->object($obj)->table("jobs")->where("IdJob")->update();

                }

                return true;
            }

        #endregion
    }