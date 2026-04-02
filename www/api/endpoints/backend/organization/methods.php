<?php

    namespace Backend\Organization;

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

        private $id = "IdOrganization";
        private $table_name = "organizations";
        private $table_translations_name = "organizations_translations";
        private $table_images_name = "organizations_images";

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }      
        #endregion
        
        #region Public Methods

            // Get
            public function get($idOrganization, $isValid = 0) {

                // Check if Logged can see the organization
                if(!$this->checkIfLoggedCan($idOrganization))
                    return $this->Unauthorized(null, "You don't have permission to see this organization");

                // Set the value of the where
                $where = ($isValid == 1) ? "AND IsValid = 1" : "";

                // Get
                $response = $this->__linq->fromDB($this->table_name)->whereDB($this->id . " = $idOrganization AND IsDeleted = 0 $where")->getFirstOrDefault();

                // Check if is not null
                if (Base_Functions::IsNullOrEmpty($response))
                    return $this->Not_Found();

                // Format
                return $this->Success($this->formatOrganization($response));
            }
            public function getAll($idsOrganizations = []) {

                // Get the request
                $request = $this->Request;

                // Build the inner join for the categories
                $inner_join_categories = $this->buildCategoryWhere(Base_Category_Type::ORGANIZATION, $request->IdsCategories ?? [], "o.IdOrganization");

                // Add the where for the ids organizations
                $idsWhere = !Base_Functions::IsNullOrEmpty($idsOrganizations) ? " AND o.IdOrganization IN (" . implode(", ", $idsOrganizations) . ")" : "";

                // Get the organization
                $sql = "SELECT o.*
                        FROM organizations o
                        WHERE o.IsValid = 1 AND o.IsDeleted = 0 $idsWhere $inner_join_categories";
                $all = $this->__linq->queryDB($sql)->getResults();

                // Check if is not null
                if (!$this->Success)
                    return $this->Not_Found();

                // Return
                return $this->Success($this->formatOrganization($all));
            }

            // Post
            public function create() {

                // Create a new row in the organizations table
                $idOrganization = $this->__opHelper->object($this->id)->table($this->table_name)->insertIncrement();

                // Check if created
                if(is_numeric($idOrganization) && $idOrganization > 0)
                    return $this->Success($idOrganization);

                return $this->Internal_Server_Error();
            }
 
            // Put
            public function update() {

                // Get the request
                $request = $this->Request;

                // Check if the organization exists
                $this->get($request->IdOrganization);

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
                $this->category->updateByType(Base_Category_Type::ORGANIZATION, $request->IdOrganization, $request->Categories ?? [], $request->MainCategory ?? null);

                // Update the organization
                $this->__opHelper->object($request)->table($this->table_name)->where($this->id)->update();

                // Init the array for the translation values
                $translation_values = array();

                // Cycle all languages
                foreach ($languages as $language) {
                    
                    $obj = new stdClass();
                    $obj->IdOrganization = $request->IdOrganization;
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
                $sql = "DELETE FROM $this->table_translations_name WHERE $this->id = $request->IdOrganization";
                $this->__linq->queryDB($sql)->getResults();

                // Insert the languages
                if(count($translation_values) > 0)
                    $this->__opHelper->table($this->table_translations_name)->insertMassive("(IdOrganization, Description, SmallDescription, IdLanguage, IsValid)", implode(", ", $translation_values));

                // Refresh cache
                $this->refreshCache();

                return $this->Success();
            }

            // Delete
            public function delete($idOrganization) {

                // Check that the organization exists
                $this->get($idOrganization);

                // Check if success 
                if ($this->Success == true) {

                    // Update the organization to deleted
                    $obj = new stdClass();
                    $obj->IdOrganization = $idOrganization;
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

                    // Check that the organization exists
                    $this->get($request->IdOrganization);

                    // Check that is success
                    if ($this->Success) {

                        // Image
                        $response = $this->__linq->fromDB("organizations_images")->whereDB("IdImage = $request->ContentRefId")->getFirstOrDefault();

                        // Check it the $response is not null
                        if (!Base_Functions::IsNullOrEmpty($response))
                            return $this->Success($response);
                    }

                    return $this->Not_Found();
                }
                public function getContents($idOrganization) {

                    $sql = "SELECT *
                            FROM (
                                SELECT ni.IdImage AS Id, ni.FullPath AS Preview, " . Base_Files_Types::IMAGE . " AS Type, ni.OrderNumber
                                FROM organizations_images ni
                                WHERE ni.IdImage = $idOrganization

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

                    // Check that the organization is not null
                    $this->get($request->IdOrganization);

                    // Check if is success
                    if ($this->Success == true) {
    
                        // Cycle the Order array
                        foreach($request->Order as $organization) {

                            // Create the update object
                            $obj = new stdClass();
                            $obj->IdImage = $organization->Id;
                            $obj->OrderNumber = $organization->OrderNumber;

                            // Update the organizations_images table
                            $this->__opHelper->object($obj)->table("organizations_images")->where("IdImage")->update();
                         
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
                    if(!Base_File::deleteContentManager($request->ContentRefId, Base_Files::ORGANIZATION,  Base_Files_Types::IMAGE))
                        return $this->Not_Found();

                    $this->refreshCache();
                    return $this->Success();
                }

                #region Images

                    // Post
                    public function saveImages() {

                        // Get the request
                        $request = $this->Request;

                        // Check that the organization is not null
                        $this->get($request->IdOrganization);

                        // Check if is success
                        if ($this->Success == true) {

                            // Save the images
                            Base_File::saveContentManager($request->IdOrganization, Base_Files::ORGANIZATION, Base_Files_Types::IMAGE);

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

                    // Get the organization Update more than 30 days and having IsDeleted = 1
                    $organization = $this->__linq->fromDB("organization")->whereDB("IsDeleted = 1 AND DATE_FORMAT(UpdateDate, '%Y-%m-%d') = '$expiration_date'")->getResults();

                    // Check that the organization array is not null
                    if (count($organization) > 0) {

                        // Get the IdOrganization array
                        $ids_organizations = array_column($organization, "IdOrganization");

                        // Get the id organization string
                        $ids_organizations_string = implode(", ", $ids_organizations);    

                        // Delete from images table
                        $sql = "DELETE FROM organizations_images WHERE IdOrganization IN ($ids_organizations_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // Delete from translations table
                        $sql = "DELETE FROM organizations_translations WHERE IdOrganization IN ($ids_organizations_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // Cycle all organization
                        foreach ($ids_organizations as $id_organization) {

                            // Delete all files of the organization
                            Base_Functions::deleteFiles($_SERVER["DOCUMENT_ROOT"] . Base_Path::ORGANIZATION . $id_organization);
                        }
                    }
                }

            #endregion

        #endregion

        #region Private Methods

            private function formatOrganization($organizations) {

                // Check if the $organizations is null
                if (Base_Functions::IsNullOrEmpty($organizations))
                    return null;

                // Check if the $organizations is an array
                $isAll = is_array($organizations);

                // If is not an array, convert it in an array with only one element
                if (!$isAll)
                    $organizations = array($organizations);

                // Get all the IdOrganization
                $ids_organizations = array_column($organizations, $this->id);

                // Get content translations
                $organization_translations = $this->__linq->reorder($this->__linq->fromDB($this->table_translations_name)->whereDB("$this->id IN (" . implode(", ", $ids_organizations) . ")")->getResults(), $this->id, true);

                // Get Images
                $organization_images = $this->__linq->reorder($this->__linq->selectDB("$this->id, FullPath, FileName")->fromDB($this->table_images_name)->whereDB("$this->id IN (" . implode(", ", $ids_organizations) . ")")->getResults(), $this->id, true);

                // Get categories
                $organization_categories = $this->category->getAll(Base_Category_Type::ORGANIZATION, $ids_organizations);

                // Build the response array
                $response = [];

                // Cycle all organizations
                foreach ($organizations as $organization) {

                    // Build object
                    $tmp = new stdClass();
                    $tmp->IdOrganization = $organization->IdOrganization;
                    $tmp->Name = $organization->Name;
                    $tmp->UseOnlyCoordinates = $organization->UseOnlyCoordinates;
                    $tmp->Phone = $organization->Phone;
                    $tmp->Notes = $organization->Notes;
                    $tmp->Latitude = $organization->Latitude;
                    $tmp->Longitude = $organization->Longitude;
                    $tmp->Address = $organization->Address;
                    $tmp->City = $organization->City;
                    $tmp->IsActive = $organization->IsActive;

                    // Check if the organization has images
                    $tmp->Images = (property_exists($organization_images, $organization->IdOrganization)) ? $organization_images->{$organization->IdOrganization} : [];

                    // Get the categories of the organization
                    $categories = ($organization_categories && property_exists($organization_categories, $organization->IdOrganization)) ? $organization_categories->{$organization->IdOrganization} : [];

                    // Get the main category
                    $main_category = array_filter($categories, function($category) {
                        return $category->IsMain == 1;
                    })[0] ?? null;

                    // Get tmp translations
                    $tmp_translations = (property_exists($organization_translations, $organization->IdOrganization)) ? $organization_translations->{$organization->IdOrganization} : [];

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

                    // Push to response
                    $response[] = $tmp;
                }
                
                // Return only one if is not an array
                return $isAll ? $response : $response[0];
            } 
            
            private function refreshCache() {

                Base_Cache_Manager::setOrganizationAllCache();
            }

        #endregion

    }