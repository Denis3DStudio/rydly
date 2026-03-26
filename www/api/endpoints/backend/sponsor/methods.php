<?php

    namespace Backend\Sponsor;

    use stdClass;
    use Base_File;
    use Base_Path;
    use Base_Files;
    use Base_Methods;
    use Base_Functions;
    use Base_Languages;
    use Base_Files_Types;
    use Base_Cache_Manager;

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

                // Get the place
                $place = $this->__linq->fromDB($this->table_name)->whereDB($this->id . " = $idSponsor AND IsDeleted = 0 $where")->getFirstOrDefault();

                // Get the place categories
                $place->Categories = [];

                // Get the place blog
                $place->News = [];

                // Get traveler path
                $place->IdSurveyQuestionAnswers = [];

                if(!Base_Functions::IsNullOrEmpty($place))
                    return $this->Success($this->formatPlace($place));

                return $this->Not_Found();
            }
            public function getAll() {

                $request = $this->Request;

                // Get the place
                $sql = "SELECT s.*, st.*

                        FROM sponsors s
                        INNER JOIN sponsors_translations st ON s.IdSponsor = st.IdSponsor AND st.IdLanguage = (SELECT MIN(st2.IdLanguage)
                                                                                                        FROM sponsors_translations st2
                                                                                                        WHERE st2.IdSponsor = s.IdSponsor
                                                                                                        )
                        WHERE s.IsValid = 1 AND s.IsDeleted = 0;";

                $all = $this->__linq->queryDB($sql)->getResults();
                $response = array();


                
                
                // Check that $all is not null
                if (count($all) > 0) {
                    
                    // Get the selection of the places
                    $categories_selected = $this->__linq->reorder($this->__linq->fromDB("categories_places_parents")->getResults(), "IdSponsor", true);
                    // Get the translations of the categories
                    // $sql = "SELECT cp.IdCategory, cpt.Title, cp.OrderNumber
                    //         FROM categories_places cp
                    //         INNER JOIN categories_places_translations cpt ON cp.IdCategory = cpt.IdCategory AND cpt.IdLanguage = " . Base_Languages::ITALIAN . "
                    //         WHERE cp.IsValid = 1 AND cp.IsDeleted = 0 
                    //         ORDER BY cpt.Title ASC";

                    $categories = [];
                    $categories_reordered = $this->__linq->reorder($categories, "IdCategory");

                    $all = $this->__linq->reorder($all, $this->id, true);

                    // Cycle all data
                    foreach($all as $translations) {

                        // Get the first translation
                        $translation = $translations[0];

                        // Create the obj for the response
                        $obj = new stdClass();
                        $obj->{$this->id} = $translation->{$this->id};
                        $obj->IsActive = $translation->IsActive;
                        $obj->IsClaimed = $translation->IsClaimed;
                        $obj->Name = $translation->Name;
                        $obj->City = $translation->City;
                        $obj->Address = $translation->Address;
                        $obj->Categories = "";
                        $obj->MainCategory = "";

                        // Check if the place has categories
                        if(property_exists($categories_selected, $translation->IdSponsor)) {

                            // Get the categories of the place
                            $place_categories_selected = array_column($categories_selected->{$translation->IdSponsor}, "IdCategory");
                            // Get the main category of the place
                            $main_category_selected  = (count($place_categories_selected) > 0) ? $categories_selected->{$translation->IdSponsor}[array_search(1, array_column($categories_selected->{$translation->IdSponsor}, 'IsMain'))]->IdCategory : null;

                            $obj->MainCategory = !Base_Functions::IsNullOrEmpty($main_category_selected) && property_exists($categories_reordered, $main_category_selected) ? $categories_reordered->{$main_category_selected}->Title : null;
                            $obj->Categories = implode(", ", array_column(array_filter($categories, function($category) use ($place_categories_selected) {
                                return in_array($category->IdCategory, $place_categories_selected);
                            }), "Title"));
                        }

                        $obj->IdLanguages = array_column($translations, "IdLanguage");

                        array_push($response, $obj);
                    }
                }

                return $this->Success($response);
            }

            // Post
            public function create() {

                // Create a new row in the places table
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

                // Check if the place exists
                $this->get($request->IdSponsor);

                // Check if is success
                if ($this->Success == true) {

                    // Get the languages
                    $languages = $request->Languages;

                    // Remove Languages from request
                    unset($request->Languages);

                    // Add IsValid to 1
                    $request->IsValid = 1;

                    // Delete all the categories of the place
                    $obj = new stdClass();
                    $obj->IdSponsor = $request->IdSponsor;
                    $this->__opHelper->object($obj)->table("categories_places_parents")->where($this->id)->delete();

                    // Update category if not -1
                    $values = '';
                    $fields = '(IdSponsor, IdCategory, IsMain)';
                    foreach($request->Category as $category) {
                        $is_main = ($request->MainCategory == $category) ? 1 : 0;
                        $values .= "({$request->IdSponsor}, $category, $is_main), ";
                    }
                    $values = rtrim($values, ', ');
                    
                    $this->__opHelper->table("categories_places_parents")->insertMassive($fields, $values);


                    // Delete all the news of the place
                    $obj = new stdClass();
                    $obj->IdSponsor = $request->IdSponsor;
                    $this->__opHelper->object($obj)->table("news_places")->where($this->id)->delete();

                    // Update category if not -1
                    if($request->IdNews != null) {
                        $values = '';
                        $fields = '(IdSponsor, IdNews)';
                        foreach($request->IdNews as $news) {
                            $values .= "({$request->IdSponsor}, $news), ";
                        }
                        $values = rtrim($values, ', ');
                        
                        $this->__opHelper->table("news_places")->insertMassive($fields, $values);
                    }
                   
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
                    $this->__opHelper->table($this->table_translations_name)->insertMassive("(IdSponsor, Description, SmallDescription, IdLanguage, IsValid)", implode(", ", $translation_values));

                    // Delete the old traveler path answers
                    $sql = "DELETE FROM surveys_questions_answers_places WHERE IdSponsor = $request->IdSponsor";
                    $this->__linq->queryDB($sql)->getResults();

                    if (!Base_Functions::IsNullOrEmpty($request->IdSurveyQuestionAnswers)) {
                        
                        $values = "($request->IdSponsor, " . implode("), ($request->IdSponsor, ", $request->IdSurveyQuestionAnswers) . ")";
                        $this->__opHelper->table("surveys_questions_answers_places")->insertMassive("(IdSponsor, IdSurveyQuestionAnswer)", $values);
                    }

                    // Refresh cache
                    $this->refreshCache();
                    return $this->Success();
                }

                return $this->Not_Found();
            }

            // Delete
            public function delete($idSponsor) {

                // Check that the place exists
                $this->get($idSponsor);

                // Check if success 
                if ($this->Success == true) {

                    // Update the place to deleted
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

                    // Check that the place exists
                    $this->get($request->IdSponsor);

                    // Check that is success
                    if ($this->Success) {

                        // Image
                        $response = $this->__linq->fromDB("places_images")->whereDB("IdImage = $request->ContentRefId")->getFirstOrDefault();

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
                                FROM places_images ni
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

                    // Check that the place is not null
                    $this->get($request->IdSponsor);

                    // Check if is success
                    if ($this->Success == true) {
    
                        // Cycle the Order array
                        foreach($request->Order as $place) {

                            // Create the update object
                            $obj = new stdClass();
                            $obj->IdImage = $place->Id;
                            $obj->OrderNumber = $place->OrderNumber;

                            // Update the places_images table
                            $this->__opHelper->object($obj)->table("places_images")->where("IdImage")->update();
                         
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
                    if(!Base_File::deleteContentManager($request->ContentRefId, Base_Files::PLACE,  Base_Files_Types::IMAGE))
                        return $this->Not_Found();

                    $this->refreshCache();
                    return $this->Success();
                }

                #region Images

                    // Post
                    public function saveImages() {

                        // Get the request
                        $request = $this->Request;

                        // Check that the place is not null
                        $this->get($request->IdSponsor);

                        // Check if is success
                        if ($this->Success == true) {

                            // Save the images
                            Base_File::saveContentManager($request->IdSponsor, Base_Files::PLACE, Base_Files_Types::IMAGE);

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

                    // Get the place Update more than 30 days and having IsDeleted = 1
                    $place = $this->__linq->fromDB("place")->whereDB("IsDeleted = 1 AND DATE_FORMAT(UpdateDate, '%Y-%m-%d') = '$expiration_date'")->getResults();

                    // Check that the place array is not null
                    if (count($place) > 0) {

                        // Get the IdSponsor array
                        $ids_places = array_column($place, "IdSponsor");

                        // Get the id place string
                        $ids_places_string = implode(", ", $ids_places);    

                        // Delete from images table
                        $sql = "DELETE FROM places_images WHERE IdSponsor IN ($ids_places_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // Delete from translations table
                        $sql = "DELETE FROM places_translations WHERE IdSponsor IN ($ids_places_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // Cycle all place
                        foreach ($ids_places as $id_place) {

                            // Delete all files of the place
                            Base_Functions::deleteFiles($_SERVER["DOCUMENT_ROOT"] . Base_Path::PLACE . $id_place);
                        }
                    }
                }

            #endregion

            #region News

                // Get All Available News
                public function getAllNews() {

                    // Get all available news (valid and not deleted)
                    $sql = "SELECT n.IdNews, n.Author, n.Date, nt.Title
                            FROM news n 
                            INNER JOIN news_translations nt ON n.IdNews = nt.IdNews AND nt.IdLanguage = 1
                            WHERE n.IsDeleted = 0 AND n.IsValid = 1
                            ORDER BY nt.Title ASC";

                    $news = $this->__linq->queryDB($sql)->getResults();

                    return $this->Success($news);
                }

            #endregion

        #endregion

        #region Private Methods

            private function formatPlace($place, $place_translations = null, $place_images = null, $place_categories = null, $isAll = false) {

                // Get content translations
                if($place_translations == null)
                    $place_translations = $this->__linq->fromDB($this->table_translations_name)->whereDB("IdSponsor = $place->IdSponsor")->getResults();
                // get Image
                if($place_images == null)
                    $place_images = $this->__linq->selectDB("IdSponsor, FullPath, FileName")->fromDB($this->table_images_name)->whereDB("IdSponsor = $place->IdSponsor")->getResults();

                // Reorder
                $place_translations = $this->__linq->reorder($place_translations, "IdSponsor", true);
                $place_images = $this->__linq->reorder($place_images, "IdSponsor", true);

                // Build object
                $response = new stdClass();
                $response->IdSponsor = $place->IdSponsor;
                $response->UseOnlyCoordinates = $place->UseOnlyCoordinates;
                $response->Name = $place->Name;
                $response->Phone = $place->Phone;
                $response->Notes = $place->Notes;
                $response->Latitude = $place->Latitude;
                $response->Longitude = $place->Longitude;
                $response->Address = $place->Address;
                $response->City = $place->City;
                $response->IsActive = $place->IsActive;
                $response->IsClaimed = $place->IsClaimed;
                $response->LanguagesIds = array();
                $response->Image = (property_exists($place_images, $place->IdSponsor)) ? $place_images->{$place->IdSponsor}[0] : array();
                $response->News = !Base_Functions::IsNullOrEmpty($place->News) ? array_column($place->News, 'IdNews') : '-1';

                if(!$isAll) {
                    $response->Languages = array();
                    $response->IdSurveyQuestionAnswers = $place->IdSurveyQuestionAnswers;
                    $response->Categories = !Base_Functions::IsNullOrEmpty($place->Categories) ? array_column($place->Categories, 'IdCategory') : array();
                    $response->MainCategory = Base_Functions::IsNullOrEmpty($response->Categories) ? null : $place->Categories[array_search(1, array_column($place->Categories, 'IsMain'))]->IdCategory;
                }
                else {
                    
                    $response->Categories = array();

                    // Check if the place_categories is not null
                    if (!Base_Functions::IsNullOrEmpty($place_categories)) {

                        // reorder by category
                        $place_categories = $this->__linq->reorder($place_categories, "IdCategory", true);

                        // Cycle all place categories
                        foreach ($place_categories as $place_category) {
                            // Push the first translation of the category
                            array_push($response->Categories, $place_category[0]->Title);
                        }
                    }
                }
                
                // Check translations
                if(property_exists($place_translations, $place->IdSponsor)) {

                    if ($isAll) {

                        // Get translations
                        $response->Description = $place_translations->{$place->IdSponsor}[0]->Description;
                        $response->SmallDescription = $place_translations->{$place->IdSponsor}[0]->SmallDescription;
                        $response->LanguagesIds = array_column($place_translations->{$place->IdSponsor}, "IdLanguage");
                    }   
                    else {

                        // Get translations
                        $place_translations = $place_translations->{$place->IdSponsor};

                        foreach ($place_translations as $translation) {
    
                            // Create the obj of the translation
                            $obj = new stdClass();
                            //$obj->Title = $translation->Title;
                            $obj->SmallDescription = $translation->SmallDescription;
                            $obj->Description = $translation->Description;
                            $obj->IsValid = $translation->IsValid;
                            $obj->IdLanguage = $translation->IdLanguage;
                            if($isAll)
                                $obj->Categories = $this->getCategoryByLang($response->IdSponsor, $obj->IdLanguage);
                            
                            // Push the obj in the languages array
                            array_push($response->Languages, $obj);
                        }
                    }
                }
                
                return $response;
            }
            private function getCategoryByLang($idSponsor, $language) {
                $response = '';

                $categories = array_column($this->__linq->selectDB('IdCategory')->fromDB("categories_places_parents")->whereDB("IdSponsor = $idSponsor")->getResults(), 'IdCategory');

                if(count($categories) > 0) {
                    foreach($categories as $category) {
                        $categoryName = $this->__linq->selectDB('Description')->fromDB("places_translations")->whereDB("IdCategory = $category AND IdLanguage = $language")->getFirstOrDefault();
                        if(!Base_Functions::IsNullOrEmpty($categoryName))
                            $response .= $categoryName->Description . ', ';
                    }
                } 
                
                return rtrim($response, ', ');
            }     
            
            private function refreshCache() {

                Base_Cache_Manager::setPlacesAllCache();
            }

        #endregion

    }