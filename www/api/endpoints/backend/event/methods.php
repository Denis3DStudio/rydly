<?php

    namespace Backend\Event;

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

        private $id = "IdEvent";
        private $table_name = "events";
        private $table_translations_name = "events_translations";
        private $table_images_name = "events_images";

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }      
        #endregion
        
        #region Public Methods

            // Get
            public function get($idEvent, $isValid = 0) {

                // Set the value of the where
                $where = ($isValid == 1) ? "AND IsValid = 1" : "";

                // Get the event
                $event = $this->__linq->fromDB($this->table_name)->whereDB($this->id . " = $idEvent AND IsDeleted = 0 $where")->getFirstOrDefault();

                // Check if the event is null
                if(Base_Functions::IsNullOrEmpty($event))
                    return $this->Not_Found();

                // Format the event
                return $this->Success($this->format($event));

            }
            public function getAll() {

                // Get the request
                $request = $this->Request;

                // Get the categories filter
                $idsCategories = property_exists($request, "IdsCategories") ? $request->IdsCategories : "";

                // Set the category where
                $categories_where = $this->buildCategoryWhere(Base_Category_Type::EVENT, $idsCategories, "p.IdEvent");

                // Build the delegation where
                $delegation_where = $this->buildOrganizationWhere("p");

                // Create the query
                $body_query = "FROM events p
                                WHERE p.IsValid = 1 AND p.IsDeleted = 0" . $delegation_where . $categories_where;

                // Get the event
                $sql = "SELECT p.*
                        $body_query";

                return $this->Success($this->format($this->__linq->queryDB($sql)->getResults()));
            }

            // Post
            public function create() {

                // Get the request
                $request = $this->Request ?? new stdClass();

                // Check if Logged is an Organization user
                if ($this->Logged->IdOrganization)
                    $request->IdOrganization = $this->Logged->IdOrganization;

                // Create a new row in the events table
                $idEvent = $this->__opHelper->object($request)->table($this->table_name)->insert();

                // Check if created
                if(!is_numeric($idEvent) || $idEvent < 0)
                    return $this->Not_Found();

                // Return the id of the event
                return $this->Success($idEvent);
            }
 
            // Put
            public function update() {

                // Get the request
                $request = $this->Request;

                // Check if the event exists
                $event = $this->get($request->IdEvent);

                // Check if is success
                if (!$this->Success) 
                    return $this->Not_Found();

                // Check if Logged can edit the event
                if (!$this->checkIfLoggedCan($event->IdOrganization))
                    return $this->Unauthorized();

                // Get the languages
                $languages = $request->Languages;

                // Remove Languages from request
                unset($request->Languages);

                // Add IsValid to 1
                $request->IsValid = 1;

                // Update the event
                $this->__opHelper->object($request)->table($this->table_name)->where($this->id)->update();

                // Call category method to update the categories of the event
                $this->category->updateByType(Base_Category_Type::EVENT, $request->IdEvent, $request->IdsCategories, $request->MainCategory);

                // Init the array for the translation values
                $translation_values = array();

                // Cycle all languages
                foreach ($languages as $language) {
                    
                    $obj = new stdClass();
                    $obj->IdEvent = $request->IdEvent;
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
                $sql = "DELETE FROM $this->table_translations_name WHERE $this->id = $request->IdEvent";
                $this->__linq->queryDB($sql)->getResults();

                // Insert the languages
                $this->__opHelper->table($this->table_translations_name)->insertMassive("(IdEvent, Description, SmallDescription, IdLanguage, IsValid)", implode(", ", $translation_values));

                // Refresh cache
                // $this->refreshCache();

                return $this->Success();
                
            }

            // Delete
            public function delete($idEvent) {

                // Check that the event exists
                $event = $this->get($idEvent);

                // Check if success 
                if (!$this->Success)
                    return $this->Not_Found();

                // Check if Logged can delete the event
                if (!$this->checkIfLoggedCan($event->IdOrganization))
                    return $this->Unauthorized();

                // Update the event to deleted
                $obj = new stdClass();
                $obj->IdEvent = $idEvent;
                $obj->IsDeleted = 1;

                // Update
                $this->__opHelper->object($obj)->table($this->table_name)->where($this->id)->update();

                // $this->refreshCache();

                return $this->Success();
            }
            
            #region Contents

                // Get
                public function getContent() {

                    // Get the request
                    $request = clone($this->Request);

                    // Check that the event exists
                    $this->get($request->IdEvent);

                    // Check that is success
                    if ($this->Success) {

                        // Image
                        $response = $this->__linq->fromDB("events_images")->whereDB("IdImage = $request->ContentRefId")->getFirstOrDefault();

                        // Check it the $response is not null
                        if (!Base_Functions::IsNullOrEmpty($response))
                            return $this->Success($response);
                    }

                    return $this->Not_Found();
                }
                public function getContents($idEvent) {

                    $sql = "SELECT *
                            FROM (
                                SELECT ni.IdImage AS Id, ni.FullPath AS Preview, " . Base_Files_Types::IMAGE . " AS Type, ni.OrderNumber
                                FROM events_images ni
                                WHERE ni.IdImage = $idEvent

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

                    // Check that the event is not null
                    $this->get($request->IdEvent);

                    // Check if is success
                    if ($this->Success == true) {
    
                        // Cycle the Order array
                        foreach($request->Order as $event) {

                            // Create the update object
                            $obj = new stdClass();
                            $obj->IdImage = $event->Id;
                            $obj->OrderNumber = $event->OrderNumber;

                            // Update the events_images table
                            $this->__opHelper->object($obj)->table("events_images")->where("IdImage")->update();
                         
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
                    if(!Base_File::deleteContentManager($request->ContentRefId, Base_Files::EVENT,  Base_Files_Types::IMAGE))
                        return $this->Not_Found();

                    $this->refreshCache();
                    return $this->Success();
                }

                #region Images

                    // Post
                    public function saveImages() {

                        // Get the request
                        $request = $this->Request;

                        // Check that the event is not null
                        $this->get($request->IdEvent);

                        // Check if is success
                        if ($this->Success == true) {

                            // Save the images
                            Base_File::saveContentManager($request->IdEvent, Base_Files::EVENT, Base_Files_Types::IMAGE);

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

                    // Get the event Update more than 30 days and having IsDeleted = 1
                    $event = $this->__linq->fromDB("event")->whereDB("IsDeleted = 1 AND DATE_FORMAT(UpdateDate, '%Y-%m-%d') = '$expiration_date'")->getResults();

                    // Check that the event array is not null
                    if (count($event) > 0) {

                        // Get the IdEvent array
                        $ids_places = array_column($event, "IdEvent");

                        // Get the id event string
                        $ids_places_string = implode(", ", $ids_places);    

                        // Delete from images table
                        $sql = "DELETE FROM events_images WHERE IdEvent IN ($ids_places_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // Delete from translations table
                        $sql = "DELETE FROM events_translations WHERE IdEvent IN ($ids_places_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // Cycle all event
                        foreach ($ids_places as $id_place) {

                            // Delete all files of the event
                            Base_Functions::deleteFiles($_SERVER["DOCUMENT_ROOT"] . Base_Path::EVENT . $id_place);
                        }
                    }
                }

            #endregion

        #endregion

        #region Private Methods

            private function format($events) {

                // Check if empty array
                if (is_array($events) && count($events) == 0)
                    return [];

                // Check if the event is not null
                if (Base_Functions::IsNullOrEmpty($events))
                    return null;

                // Check if is an array
                $isAll = is_array($events);

                // If is not an array, convert in array
                if (!$isAll)
                    $events = array($events);
            
                // Get all idsPlaces
                $idsPlaces = array_unique(array_column($events, "IdEvent"));

                // Get all the translations of the events
                $events_translations = $this->__linq->reorder($this->__linq->fromDB($this->table_translations_name)->whereDB("IdEvent IN (" . implode(", ", $idsPlaces) . ")")->getResults(), $this->id, true);

                // Get Images
                $events_images = $this->__linq->reorder($this->__linq->selectDB("IdEvent, FullPath, FileName")->fromDB($this->table_images_name)->whereDB("IdEvent IN (" . implode(", ", $idsPlaces) . ")")->getResults(), $this->id, true);

                // Get Categories
                $places_categories = $this->category->getAll(Base_Category_Type::EVENT, $idsPlaces);

                // Get all the organizations of the events
                $organizations = $this->__linq->reorder($this->organization->getAll($idsPlaces), "IdOrganization");

                // Init the response array
                $response = [];

                // Cicle all events
                foreach ($events as $event) {

                    // Build object
                    $tmp = new stdClass();
                    $tmp->IdEvent = $event->IdEvent;
                    $tmp->IdOrganization = $event->IdOrganization;
                    $tmp->UseOnlyCoordinates = $event->UseOnlyCoordinates;
                    $tmp->Name = $event->Name;
                    $tmp->Phone = $event->Phone;
                    $tmp->Notes = $event->Notes;
                    $tmp->Latitude = $event->Latitude;
                    $tmp->Longitude = $event->Longitude;
                    $tmp->Address = $event->Address;
                    $tmp->City = $event->City;
                    $tmp->IsActive = $event->IsActive;
                    $tmp->IsClaimed = $event->IsClaimed;

                    // Add organization
                    $tmp->Organization = (property_exists($organizations, $event->IdOrganization)) ? $organizations->{$event->IdOrganization} : null;

                    // Get Images
                    $tmp->Images = (property_exists($events_images, $event->IdEvent)) ? $events_images->{$event->IdEvent} : [];

                    // Get categories
                    $tmps_categories = (property_exists($places_categories, $event->IdEvent)) ? $places_categories->{$event->IdEvent} : [];

                    // Get Main Category
                    $tmp_mainCategory = array_filter($tmps_categories, function($category) {
                        return $category->IsMain == 1;
                    }) ?? null;

                    // Check if is not all (used for the getAll method to avoid to return too much data)
                    if(!$isAll) {
                        
                        // Add translations
                        $tmp->Languages = (property_exists($events_translations, $event->IdEvent)) ? $events_translations->{$event->IdEvent} : "";

                        // Add Categories
                        $tmp->IdsCategories = array_column($tmps_categories, "IdCategory");

                        // Add Main Category
                        $tmp->MainCategory = $tmp_mainCategory ? $tmp_mainCategory[0]->IdCategory : null;
                    }
                    else {
                        
                        // Add Categories Names only for the getAll method
                        $tmp->Categories = $tmps_categories;

                        // Main Category Name
                        $tmp->MainCategory = $tmp_mainCategory ? $tmp_mainCategory[0] : null;
                    }

                    // Add the response to the event
                    $response[] = $tmp;
                }

                return $isAll ? $response : $response[0];
            }            
            private function refreshCache() {

                Base_Cache_Manager::setPlacesAllCache();
            }

        #endregion

    }