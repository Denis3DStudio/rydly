<?php

    namespace Backend\Utility;

    use stdClass;
    use Base_Cache;
    use Base_File;
    use Base_Files;
use Base_Files_Captions_Types;
use Base_Files_Extentions;
    use Base_Files_Types;
    use Base_Functions;
    use Base_Methods;

    class Methods extends Base_Methods {

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }
        #endregion

        #region Public Methods

            public function getPlaces() {

                $response = Base_Cache::get("PLACES");

                if($response == false) {
    
                    // Get all data of the places
                    $sql = "SELECT *
                            FROM (
                                /*(SELECT DISTINCT 'Region' AS Reference, IdRegion AS Id, IdRegion AS OptionKey, IdRegion AS OptionValue, Region AS OptionName, '' AS Abbreviation, 1 AS OrderNumber
                                FROM regions
                                ORDER BY Region ASC)
                                
                                UNION*/

                                (SELECT DISTINCT 'Province' AS Reference, IdProvince AS Id, IdRegion AS OptionKey, IdProvince AS OptionValue, ProvinceName AS OptionName, Province AS Abbreviation , 2 AS OrderNumber
                                FROM provinces
                                ORDER BY ProvinceName ASC)
                                    
                                UNION

                                (SELECT DISTINCT 'City' AS Reference, IdCity AS Id, IdProvince AS OptionKey, IdCity AS OptionValue, City AS OptionName, '' AS Abbreviation, 3 AS OrderNumber
                                FROM cities
                                ORDER BY City ASC)
                                
                                UNION

                                (SELECT DISTINCT 'ZipCode' AS Reference, z.IdZipCode AS Id, c.City AS OptionKey, z.ZipCode AS OptionValue, z.ZipCode AS OptionName, '' AS Abbreviation, 4 AS OrderNumber
                                FROM zipcodes z
                                INNER JOIN cities c ON c.IdCity = z.IdCity
                                ORDER BY z.ZipCode ASC)
                                ) AS t
                            ORDER BY OrderNumber ASC
                    ";

                    $data = $this->__linq->reorder($this->__linq->queryDB($sql)->getResults(), "Reference");

                    // reorder all values by previous key
                    $response = new stdClass();
                    foreach ($data as $reference => $values) {

                        $response->{$reference} = $this->__linq->reorder($values, "OptionKey");
                    }

                    $response->Country = $this->__linq->fromDB("countries")->getResults();

                    Base_Cache::set("PLACES", $response, 525600);
                }                

                $this->Success($response);
            }

            public function deleteCache() {
                Base_Cache::clear();
            }

            #region Files

                // Get
                public function getFile($idRow, $macro, $type) {

                    // Get the file
                    $file = Base_File::getContentManager($idRow, $macro, $type);

                    // Check if file is found
                    if(Base_Functions::IsNullOrEmpty($file))
                        $this->Not_Found(null, "File not found");

                    // Return the file
                    return $this->Success($this->formatFiles($file, $type));
                }
                public function getFiles($idRow, $macro, $type, $extras) {

                    // Decode the extras
                    $extras = json_decode($extras);

                    // Get the files
                    $files = Base_File::getContentsManager($idRow, $macro, $type, $extras);

                    // Return the files
                    return $this->Success($this->formatFiles($files, $type));
                }

                // Post
                public function uploadFiles($idRow, $macro, $type, $extras = null) {

                    // Check if Files is uploaded
                    if(Base_Functions::IsNullOrEmpty($_FILES))
                        $this->Not_Found(null, "No files uploaded");

                    // Decode the extras
                    if(!Base_Functions::IsNullOrEmpty($extras))
                        $extras = json_decode($extras);

                    // Upload the files
                    $files = Base_File::saveContentManager($idRow, $macro, $type, $extras);

                    // Check if files are uploaded
                    if(Base_Functions::IsNullOrEmpty($files))
                        $this->Not_Found(null, "Error uploading files");

                    return $this->Success($files);
                }

                // Put
                public function updateFile($idRow, $macro, $type, $data) {

                    // Get the table
                    $table = Base_Files::DB_TABLES_NAMES[$macro] . "_" . Base_Files_Types::DB_TABLES_NAMES[$type];

                    // Build the obj for the update
                    $obj = new stdClass();
                    $obj->{Base_Files_Types::DB_IDS_TYPES[$type]} = $idRow;

                    // Add all the data
                    foreach ($data as $key => $value)
                        $obj->{$key} = $value;

                    // Update the file
                    $this->__opHelper->object($obj)->table($table)->where(Base_Files_Types::DB_IDS_TYPES[$type], $idRow)->update();

                    // Return
                    if(!$this->Success())
                        $this->Not_Found(null, "Error updating file");

                    // If success
                    return $this->Success();
                }
                public function reorderFiles($idRow, $macro, $type, $data) {

                    // Check if Files is uploaded
                    if(Base_Functions::IsNullOrEmpty($_FILES))
                        $this->Not_Found(null, "No files uploaded");

                    // Update the files
                    Base_File::updateContenOrderManager($idRow, $macro, $type, $data);

                    // Return
                    return $this->Success();
                }

                // Delete
                public function deleteFile($idRow, $macro, $type) {

                    // Delete the files
                    $res = Base_File::deleteContentManager($idRow, $macro, $type);

                    // Check if files are deleted
                    if(!$res)
                        $this->Not_Found(null, "Error deleting files");

                    // Return
                    return $this->Success();
                }

                #region Caption

                    // Get
                    public function getFileCaptions($idFile, $macro, $idRow, $type, $format) {

                        // Get the table
                        $table = Base_Files::DB_TABLES_CAPTIONS_NAMES[$macro];
                        
                        // Get the captions
                        $captions = $this->__linq->fromDB($table)->whereDB(Base_Files::IDS_DB[$macro] . " = " . $idRow . " AND ContentRefId = " . $idFile . " AND ContentType = " . $type)->getResults();

                        // Check if captionType is single or multiple
                        return $this->Success($format == Base_Files_Captions_Types::MONO_LANG ? $captions[0] ?? null : $captions);
                    }

                    // Put
                    public function updateFileCaptions($idFile, $macro, $type, $idRow, $data) {

                        // Get the table
                        $table = Base_Files::DB_TABLES_CAPTIONS_NAMES[$macro];

                        // Init object
                        $obj = new stdClass();
                        $obj->Caption = $data->Caption;
                        $obj->{Base_Files::IDS_DB[$macro]} = $idRow;
                        $obj->ContentRefId = $idFile;
                        $obj->ContentType = $type;

                        // Delete all the captions
                        $sql = "DELETE FROM " . $table . " WHERE " . Base_Files::IDS_DB[$macro] . " = " . $idRow . " AND ContentRefId = " . $idFile . " AND ContentType = " . $type;
                        $this->__linq->queryDB($sql)->getResults();

                        // Check success
                        if(!$this->Success())
                            $this->Not_Found(null, "Error deleting captions");

                        // Check if data is array
                        if(is_array($data)) {

                            // Cicle all the data
                            foreach ($data as $key => $value) {

                                // Build the obj for the update
                                $obj = new stdClass();
                                $obj->Caption = $value->Caption;
                                $obj->Language = $value->IdLanguage;

                                // Update the file
                                $this->__opHelper->object($obj)->table($table)->insert();
                            }
                        } else if (is_object($data)) {

                            // Insert the data
                            $this->__opHelper->object($obj)->table($table)->insert();
                        }

                        // Check success
                        if(!$this->Success())
                            $this->Not_Found(null, "Error inserting captions");

                        // Return
                        return $this->Success();

                    }


                #endregion

            #endregion

        #endregion

        #region Private Methods

            private function formatFiles($files, $type) {

                // Check if single file
                $isSingle = false;

                // Check if is array
                if(!is_array($files)) {
                    $isSingle = true;
                    $files = [$files];
                }

                // Init the response
                $response = [];

                // Cicle the files
                foreach ($files as $key => $file) {

                    // Init the obj
                    $obj = new stdClass();
                    $obj->IdFile = $file->{Base_Files_Types::DB_IDS_TYPES[$type]};
                    $obj->FullPath = $file->FullPath;
                    $obj->FileName = $file->FileName;

                    // Add the extension
                    $obj->FileExtension = $this->getExtension(pathinfo($file->FullPath, PATHINFO_EXTENSION));
                    
                    // Switch the type
                    switch ($type) {
                        case Base_Files_Types::IMAGE:
                            $obj->Preview = $file->FullPath;

                            break;
                        case Base_Files_Types::VIDEO:
                            $obj->Preview = "https://img.youtube.com/vi/" . $file->VideoCode . "/hqdefault.jpg";

                            break;
                        case Base_Files_Types::ATTACHMENT:
                            

                            break;
                    }

                    // Add the obj
                    array_push($response, $obj);
                }

                // Return
                return $isSingle ? $response[0] : $response;
            }
            private function getExtension($extension) {

                // Return the extension
                foreach (Base_Files_Types::ALL as $type) {
                    if(isset(Base_Files_Types::EXTENSIONS[$type]) && in_array($extension, Base_Files_Types::EXTENSIONS[$type]))
                        return $type;
                }
                
                return Base_Files_Types::GENERIC;
            }

    }

?>