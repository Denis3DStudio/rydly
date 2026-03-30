<?php

    namespace Backend\Organizer;

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

        private $id = "IdOrganizer";
        private $table_name = "organizers";
        private $table_translations_name = "organizers_translations";
        private $table_images_name = "organizers_images";

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }      
        #endregion
        
        #region Public Methods

            // Get
            public function get($idOrganizer, $isValid = 0) {

                // Set the value of the where
                $where = ($isValid == 1) ? "AND IsValid = 1" : "";

                // Get
                $response = $this->__linq->fromDB($this->table_name)->whereDB($this->id . " = $idOrganizer AND IsDeleted = 0 $where")->getFirstOrDefault();

                // Check if is not null
                if (Base_Functions::IsNullOrEmpty($response))
                    return $this->Not_Found();

                // Format
                return $this->Success($this->formatOrganizer($response));
            }
            public function getAll() {

                // Get the request
                $request = $this->Request;

                // Build the inner join for the categories
                $inner_join_categories = "";
                if (property_exists($request, "IdsCategories") && !Base_Functions::IsNullOrEmpty($request->IdsCategories))
                    $inner_join_categories = "INNER JOIN refs_categories rc ON rc.ContentRefId = s.IdOrganizer AND rc.IdType = " . Base_Category_Type::ORGANIZER . " AND rc.IdCategory IN (" . implode(", ", $request->IdsCategories) . ")";

                // Get the organizer
                $sql = "SELECT s.*, st.*
                        FROM organizers s
                        $inner_join_categories
                        INNER JOIN organizers_translations st ON s.IdOrganizer = st.IdOrganizer AND st.IdLanguage = (SELECT MIN(st2.IdLanguage)
                                                                                                        FROM organizers_translations st2
                                                                                                        WHERE st2.IdOrganizer = s.IdOrganizer
                                                                                                        )
                        WHERE s.IsValid = 1 AND s.IsDeleted = 0";
                $all = $this->__linq->queryDB($sql)->getResults();

                // Check if is not null
                if (!$this->Success)
                    return $this->Not_Found();

                // Return
                return $this->Success($this->formatOrganizer($all));
            }

            // Post
            public function create() {

                // Create a new row in the organizers table
                $idOrganizer = $this->__opHelper->object($this->id)->table($this->table_name)->insertIncrement();

                // Check if created
                if(is_numeric($idOrganizer) && $idOrganizer > 0)
                    return $this->Success($idOrganizer);

                return $this->Internal_Server_Error();
            }
 
            // Put
            public function update() {

                // Get the request
                $request = $this->Request;

                // Check if the organizer exists
                $this->get($request->IdOrganizer);

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
                $this->category->updateByType(Base_Category_Type::ORGANIZER, $request->IdOrganizer, $request->Categories ?? [], $request->MainCategory ?? null);

                // Update the organizer
                $this->__opHelper->object($request)->table($this->table_name)->where($this->id)->update();

                // Init the array for the translation values
                $translation_values = array();

                // Cycle all languages
                foreach ($languages as $language) {
                    
                    $obj = new stdClass();
                    $obj->IdOrganizer = $request->IdOrganizer;
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
                $sql = "DELETE FROM $this->table_translations_name WHERE $this->id = $request->IdOrganizer";
                $this->__linq->queryDB($sql)->getResults();

                // Insert the languages
                if(count($translation_values) > 0)
                    $this->__opHelper->table($this->table_translations_name)->insertMassive("(IdOrganizer, Description, SmallDescription, IdLanguage, IsValid)", implode(", ", $translation_values));

                // Refresh cache
                $this->refreshCache();

                return $this->Success();
            }

            // Delete
            public function delete($idOrganizer) {

                // Check that the organizer exists
                $this->get($idOrganizer);

                // Check if success 
                if ($this->Success == true) {

                    // Update the organizer to deleted
                    $obj = new stdClass();
                    $obj->IdOrganizer = $idOrganizer;
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

                    // Check that the organizer exists
                    $this->get($request->IdOrganizer);

                    // Check that is success
                    if ($this->Success) {

                        // Image
                        $response = $this->__linq->fromDB("organizers_images")->whereDB("IdImage = $request->ContentRefId")->getFirstOrDefault();

                        // Check it the $response is not null
                        if (!Base_Functions::IsNullOrEmpty($response))
                            return $this->Success($response);
                    }

                    return $this->Not_Found();
                }
                public function getContents($idOrganizer) {

                    $sql = "SELECT *
                            FROM (
                                SELECT ni.IdImage AS Id, ni.FullPath AS Preview, " . Base_Files_Types::IMAGE . " AS Type, ni.OrderNumber
                                FROM organizers_images ni
                                WHERE ni.IdImage = $idOrganizer

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

                    // Check that the organizer is not null
                    $this->get($request->IdOrganizer);

                    // Check if is success
                    if ($this->Success == true) {
    
                        // Cycle the Order array
                        foreach($request->Order as $organizer) {

                            // Create the update object
                            $obj = new stdClass();
                            $obj->IdImage = $organizer->Id;
                            $obj->OrderNumber = $organizer->OrderNumber;

                            // Update the organizers_images table
                            $this->__opHelper->object($obj)->table("organizers_images")->where("IdImage")->update();
                         
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
                    if(!Base_File::deleteContentManager($request->ContentRefId, Base_Files::ORGANIZER,  Base_Files_Types::IMAGE))
                        return $this->Not_Found();

                    $this->refreshCache();
                    return $this->Success();
                }

                #region Images

                    // Post
                    public function saveImages() {

                        // Get the request
                        $request = $this->Request;

                        // Check that the organizer is not null
                        $this->get($request->IdOrganizer);

                        // Check if is success
                        if ($this->Success == true) {

                            // Save the images
                            Base_File::saveContentManager($request->IdOrganizer, Base_Files::ORGANIZER, Base_Files_Types::IMAGE);

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

                    // Get the organizer Update more than 30 days and having IsDeleted = 1
                    $organizer = $this->__linq->fromDB("organizer")->whereDB("IsDeleted = 1 AND DATE_FORMAT(UpdateDate, '%Y-%m-%d') = '$expiration_date'")->getResults();

                    // Check that the organizer array is not null
                    if (count($organizer) > 0) {

                        // Get the IdOrganizer array
                        $ids_organizers = array_column($organizer, "IdOrganizer");

                        // Get the id organizer string
                        $ids_organizers_string = implode(", ", $ids_organizers);    

                        // Delete from images table
                        $sql = "DELETE FROM organizers_images WHERE IdOrganizer IN ($ids_organizers_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // Delete from translations table
                        $sql = "DELETE FROM organizers_translations WHERE IdOrganizer IN ($ids_organizers_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // Cycle all organizer
                        foreach ($ids_organizers as $id_organizer) {

                            // Delete all files of the organizer
                            Base_Functions::deleteFiles($_SERVER["DOCUMENT_ROOT"] . Base_Path::ORGANIZER . $id_organizer);
                        }
                    }
                }

            #endregion

        #endregion

        #region Private Methods

            private function formatOrganizer($organizers) {

                // Check if the $organizers is null
                if (Base_Functions::IsNullOrEmpty($organizers))
                    return null;

                // Check if the $organizers is an array
                $isAll = is_array($organizers);

                // If is not an array, convert it in an array with only one element
                if (!$isAll)
                    $organizers = array($organizers);

                // Get all the IdOrganizer
                $ids_organizers = array_column($organizers, $this->id);

                // Get content translations
                $organizer_translations = $this->__linq->reorder($this->__linq->fromDB($this->table_translations_name)->whereDB("$this->id IN (" . implode(", ", $ids_organizers) . ")")->getResults(), $this->id, true);

                // Get Images
                $organizer_images = $this->__linq->reorder($this->__linq->selectDB("$this->id, FullPath, FileName")->fromDB($this->table_images_name)->whereDB("$this->id IN (" . implode(", ", $ids_organizers) . ")")->getResults(), $this->id, true);

                // Get categories
                $organizer_categories = $this->category->getAll(Base_Category_Type::ORGANIZER, $ids_organizers);

                // Build the response array
                $response = [];

                // Cycle all organizers
                foreach ($organizers as $organizer) {

                    // Build object
                    $tmp = new stdClass();
                    $tmp->IdOrganizer = $organizer->IdOrganizer;
                    $tmp->Name = $organizer->Name;
                    $tmp->UseOnlyCoordinates = $organizer->UseOnlyCoordinates;
                    $tmp->Phone = $organizer->Phone;
                    $tmp->Notes = $organizer->Notes;
                    $tmp->Latitude = $organizer->Latitude;
                    $tmp->Longitude = $organizer->Longitude;
                    $tmp->Address = $organizer->Address;
                    $tmp->City = $organizer->City;
                    $tmp->IsActive = $organizer->IsActive;

                    // Check if the organizer has images
                    $tmp->Images = (property_exists($organizer_images, $organizer->IdOrganizer)) ? $organizer_images->{$organizer->IdOrganizer} : [];

                    // Get the categories of the organizer
                    $categories = ($organizer_categories && property_exists($organizer_categories, $organizer->IdOrganizer)) ? $organizer_categories->{$organizer->IdOrganizer} : [];

                    // Get the main category
                    $main_category = array_filter($categories, function($category) {
                        return $category->IsMain == 1;
                    })[0] ?? null;

                    // Get tmp translations
                    $tmp_translations = (property_exists($organizer_translations, $organizer->IdOrganizer)) ? $organizer_translations->{$organizer->IdOrganizer} : [];

                    // Check if all the translations are valid
                    if ($isAll) {

                        // Add translations
                        $tmp->Title = $tmp_translations[0]->Description;
                        $tmp->LanguagesIds = array_column($tmp_translations, "IdLanguage");

                        // Get all the names of the categories
                        $tmp->Categories = $categories;

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

                Base_Cache_Manager::setOrganizerAllCache();
            }

        #endregion

    }