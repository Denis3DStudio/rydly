<?php

    namespace Backend\Sponsor;

    use stdClass;
    use Base_File;
    use Base_Path;
    use Base_Files;
    use Base_Methods;
    use Base_Functions;
    use Base_Files_Types;
    use Base_Cache_Manager;
    use Base_Category_Type;

    class Methods extends Base_Methods {

        private $id = "IdSponsor";
        private $table_name = "sponsors";
        private $table_translations_name = "sponsors_translations";
        private $table_images_name = "sponsors_images";

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }      
        #endregion
        
        #region Public Methods

            // Get
            public function get($idSponsor, $isValid = 0) {

                // Set the value of the where
                $where = ($isValid == 1) ? "AND IsValid = 1" : "";

                // Get
                $response = $this->__linq->fromDB($this->table_name)->whereDB($this->id . " = $idSponsor AND IsDeleted = 0 $where")->getFirstOrDefault();

                // Check if is not null
                if (Base_Functions::IsNullOrEmpty($response))
                    return $this->Not_Found();

                // Format
                return $this->Success($this->formatSponsor($response));
            }
            public function getAll() {

                $request = $this->Request;

                // Get the sponsor
                $sql = "SELECT s.*, st.*

                        FROM sponsors s
                        INNER JOIN sponsors_translations st ON s.IdSponsor = st.IdSponsor AND st.IdLanguage = (SELECT MIN(st2.IdLanguage)
                                                                                                        FROM sponsors_translations st2
                                                                                                        WHERE st2.IdSponsor = s.IdSponsor
                                                                                                        )
                        WHERE s.IsValid = 1 AND s.IsDeleted = 0;";

                $all = $this->__linq->queryDB($sql)->getResults();
                $response = array();

                $response = $this->formatSponsor($all);

                return $this->Success($response);
            }

            // Post
            public function create() {

                // Create a new row in the sponsors table
                $idSponsor = $this->__opHelper->object($this->id)->table($this->table_name)->insertIncrement();

                // Check if created
                if(is_numeric($idSponsor) && $idSponsor > 0)
                    return $this->Success($idSponsor);

                return $this->Internal_Server_Error();
            }
 
            // Put
            public function update() {

                // Get the request
                $request = $this->Request;

                // Check if the sponsor exists
                $this->get($request->IdSponsor);

                // Check if is success
                if (!$this->Success)
                    return $this->Not_Found();

                // Get the languages
                $languages = $request->Languages;

                // Remove Languages from request
                unset($request->Languages);

                // Add IsValid to 1
                $request->IsValid = 1;

                // Call category update
                $this->category->updateByType(Base_Category_Type::SPONSOR, $request->IdSponsor, $request->Categories ?? [], $request->MainCategory ?? null);

                // Update the sponsor
                $this->__opHelper->object($request)->table($this->table_name)->where($this->id)->update();

                // Init the array for the translation values
                $translation_values = array();

                // Cycle all languages
                foreach ($languages as $language) {
                    
                    $obj = new stdClass();
                    $obj->IdSponsor = $request->IdSponsor;
                    $obj->Description = $language->Description;
                    $obj->SmallDescription = $language->SmallDescription;
                    $obj->IdLanguage = $language->IdLanguage;
                    $obj->IsValid = 1;

                    $obj = Base_Functions::convertForMassive($obj, true);
                    // Create query
                    $query = "(" . implode(", ", array_values((array)$obj)) . ")";

                    array_push($translation_values, $query);
                }

                // Delete the old translations
                $sql = "DELETE FROM $this->table_translations_name WHERE $this->id = $request->IdSponsor";
                $this->__linq->queryDB($sql)->getResults();

                // Insert the languages
                if(count($translation_values) > 0)
                    $this->__opHelper->table($this->table_translations_name)->insertMassive("(IdSponsor, Description, SmallDescription, IdLanguage, IsValid)", implode(", ", $translation_values));

                // Refresh cache
                $this->refreshCache();

                return $this->Success();
            }

            // Delete
            public function delete($idSponsor) {

                // Check that the sponsor exists
                $this->get($idSponsor);

                // Check if success 
                if ($this->Success == true) {

                    // Update the sponsor to deleted
                    $obj = new stdClass();
                    $obj->IdSponsor = $idSponsor;
                    $obj->IsDeleted = 1;

                    // Update
                    $this->__opHelper->object($obj)->table($this->table_name)->where($this->id)->update();

                    return $this->Success();
                }

                $this->refreshCache();
                return $this->Not_Found();
            }
            
            #region Contents

                // Get
                public function getContent() {

                    // Get the request
                    $request = clone($this->Request);

                    // Check that the sponsor exists
                    $this->get($request->IdSponsor);

                    // Check that is success
                    if ($this->Success) {

                        // Image
                        $response = $this->__linq->fromDB("sponsors_images")->whereDB("IdImage = $request->ContentRefId")->getFirstOrDefault();

                        // Check it the $response is not null
                        if (!Base_Functions::IsNullOrEmpty($response))
                            return $this->Success($response);
                    }

                    return $this->Not_Found();
                }
                public function getContents($idSponsor) {

                    $sql = "SELECT *
                            FROM (
                                SELECT ni.IdImage AS Id, ni.FullPath AS Preview, " . Base_Files_Types::IMAGE . " AS Type, ni.OrderNumber
                                FROM sponsors_images ni
                                WHERE ni.IdImage = $idSponsor

                            ) t
                            ORDER BY OrderNumber ASC";

                    // Get the contents
                    $contents = $this->__linq->queryDB($sql)->getResults();
                    
                    return $this->Success($contents);
                }

                // Put
                public function updateContentsOrder() {

                    // Get the request
                    $request = $this->Request;

                    // Check that the sponsor is not null
                    $this->get($request->IdSponsor);

                    // Check if is success
                    if ($this->Success == true) {
    
                        // Cycle the Order array
                        foreach($request->Order as $sponsor) {

                            // Create the update object
                            $obj = new stdClass();
                            $obj->IdImage = $sponsor->Id;
                            $obj->OrderNumber = $sponsor->OrderNumber;

                            // Update the sponsors_images table
                            $this->__opHelper->object($obj)->table("sponsors_images")->where("IdImage")->update();
                         
                        }

                        $this->refreshCache();
                        return $this->Success();
                    }

                    return $this->Not_Found();
                }   

                // Delete
                public function deleteContent() {

                    // Get the request
                    $request = clone($this->Request);

                    // Delete the file
                    if(!Base_File::deleteContentManager($request->ContentRefId, Base_Files::SPONSOR,  Base_Files_Types::IMAGE))
                        return $this->Not_Found();

                    $this->refreshCache();
                    return $this->Success();
                }

                #region Images

                    // Post
                    public function saveImages() {

                        // Get the request
                        $request = $this->Request;

                        // Check that the sponsor is not null
                        $this->get($request->IdSponsor);

                        // Check if is success
                        if ($this->Success == true) {

                            // Save the images
                            Base_File::saveContentManager($request->IdSponsor, Base_Files::SPONSOR, Base_Files_Types::IMAGE);

                            $this->refreshCache();
                            return $this->Success();
                        }

                        return $this->Not_Found();
                    }

                #endregion
                
            #endregion

            #region Jobs

                public function deleteFilesAfter365days() {

                    // Create the expiration date
                    $expiration_date = date('Y-m-d', strtotime('- 365 days'));

                    // Get the sponsor Update more than 30 days and having IsDeleted = 1
                    $sponsor = $this->__linq->fromDB("sponsor")->whereDB("IsDeleted = 1 AND DATE_FORMAT(UpdateDate, '%Y-%m-%d') = '$expiration_date'")->getResults();

                    // Check that the sponsor array is not null
                    if (count($sponsor) > 0) {

                        // Get the IdSponsor array
                        $ids_sponsors = array_column($sponsor, "IdSponsor");

                        // Get the id sponsor string
                        $ids_sponsors_string = implode(", ", $ids_sponsors);    

                        // Delete from images table
                        $sql = "DELETE FROM sponsors_images WHERE IdSponsor IN ($ids_sponsors_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // Delete from translations table
                        $sql = "DELETE FROM sponsors_translations WHERE IdSponsor IN ($ids_sponsors_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // Cycle all sponsor
                        foreach ($ids_sponsors as $id_sponsor) {

                            // Delete all files of the sponsor
                            Base_Functions::deleteFiles($_SERVER["DOCUMENT_ROOT"] . Base_Path::SPONSOR . $id_sponsor);
                        }
                    }
                }

            #endregion

        #endregion

        #region Private Methods

            private function formatSponsor($sponsors) {

                // Check if the $sponsors is null
                if (Base_Functions::IsNullOrEmpty($sponsors))
                    return null;

                // Check if the $sponsors is an array
                $isAll = is_array($sponsors);

                // If is not an array, convert it in an array with only one element
                if (!$isAll)
                    $sponsors = array($sponsors);

                // Get all the IdSponsor
                $ids_sponsors = array_column($sponsors, $this->id);

                // Get content translations
                $sponsor_translations = $this->__linq->reorder($this->__linq->fromDB($this->table_translations_name)->whereDB("$this->id IN (" . implode(", ", $ids_sponsors) . ")")->getResults(), $this->id, true);

                // Get Images
                $sponsor_images = $this->__linq->reorder($this->__linq->selectDB("$this->id, FullPath, FileName")->fromDB($this->table_images_name)->whereDB("$this->id IN (" . implode(", ", $ids_sponsors) . ")")->getResults(), $this->id, true);

                // Get categories
                $sponsor_categories = $this->category->getAll(Base_Category_Type::SPONSOR, $ids_sponsors);

                // Build the response array
                $response = [];

                // Cycle all sponsors
                foreach ($sponsors as $sponsor) {

                    // Build object
                    $tmp = new stdClass();
                    $tmp->IdSponsor = $sponsor->IdSponsor;
                    $tmp->UseOnlyCoordinates = $sponsor->UseOnlyCoordinates;
                    $tmp->Phone = $sponsor->Phone;
                    $tmp->Notes = $sponsor->Notes;
                    $tmp->Latitude = $sponsor->Latitude;
                    $tmp->Longitude = $sponsor->Longitude;
                    $tmp->Address = $sponsor->Address;
                    $tmp->City = $sponsor->City;
                    $tmp->IsActive = $sponsor->IsActive;

                    // Check if the sponsor has images
                    $tmp->Images = (property_exists($sponsor_images, $sponsor->IdSponsor)) ? $sponsor_images->{$sponsor->IdSponsor} : [];

                    // Get the categories of the sponsor
                    $categories = (property_exists($sponsor_categories, $sponsor->IdSponsor)) ? $sponsor_categories->{$sponsor->IdSponsor} : [];

                    // Get the main category
                    $main_category = array_filter($categories, function($category) {
                        return $category->IsMain == 1;
                    })[0] ?? null;

                    // Get tmp translations
                    $tmp_translations = (property_exists($sponsor_translations, $sponsor->IdSponsor)) ? $sponsor_translations->{$sponsor->IdSponsor} : [];

                    // Check if all the translations are valid
                    if ($isAll) {

                        // Add translations
                        $tmp->Title = $tmp_translations[0]->Description;
                        $tmp->LanguagesIds = array_column($tmp_translations, "IdLanguage");

                        // Get all the names of the categories
                        $tmp->Categories = array_column($categories, "Title");

                        // Add the main category name
                        $tmp->MainCategory = !empty($main_category) ? $main_category->Title : (count($tmp->Categories) > 0 ? $tmp->Categories : null);

                    } else {

                        // Add translations
                        $tmp->Languages = $tmp_translations;

                        // Add the ids of the categories
                        $tmp->Categories = array_column($categories, "IdCategory");

                        // Add the main category id
                        $tmp->MainCategory = !empty($main_category) ? $main_category->IdCategory : (count($tmp->Categories) > 0 ? $tmp->Categories[0] : null);
                    }

                    $response[] = $tmp;
                }
                
                return $isAll ? $response : $response[0];
            } 
            
            private function refreshCache() {

                Base_Cache_Manager::setSponsorAllCache();
            }

        #endregion

    }