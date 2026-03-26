<?php

    namespace Backend\Category_Place;

    use stdClass;
    use Base_File;
    use Base_Files;
    use Base_Methods;
    use Base_Functions;
    use Base_Files_Types;
    use Base_Cache_Manager;

    class Methods extends Base_Methods {

        private $id = "IdCategory";
        private $table_name = "categories_places";
        private $table_translations_name = "categories_places_translations";
        private $table_images_name = "categories_places_images";

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }      
        #endregion
        
        #region Public Methods

            // Get
            public function get($idCategory) {

                // Check if the exists and is not null
                $data = $this->__linq->fromDB($this->table_name)->whereDB($this->id . " = $idCategory AND IsDeleted = 0")->getFirstOrDefault();

                // Check if the data is null
                if (Base_Functions::IsNullOrEmpty($data))
                    return $this->Not_Found(null, "Categoria non trovata!");

                // Get the translations
                $translations = $this->__linq->fromDB($this->table_translations_name)->whereDB($this->id . " = $idCategory")->getResults();

                $response = new stdClass();
                $response->General = $data;
                $response->IsDeletable = $this->checkDeletableCategories($idCategory);
                $response->Translations = $this->formatTranslations($translations);
                    
                return $this->Success($response);
            }
            public function getAll() {

                // Get all data
                $sql = "SELECT DISTINCT t.{$this->id}, t.IsActive, t.OrderNumber
                        FROM {$this->table_name} t
                        INNER JOIN {$this->table_translations_name} tt ON t.{$this->id} = tt.{$this->id}
                        WHERE t.IsValid = 1 AND t.IsDeleted = 0
                        ORDER BY t.OrderNumber ASC";

                $all = $this->__linq->queryDB($sql)->getResults();

                $response = array();

                // Check that $all is not null
                if (count($all) > 0) {

                    $all_deletable = $this->checkDeletableCategories(array_unique(array_column($all, $this->id)));

                    // Get all the images
                    $images = Base_File::getContentsManagerMultiple(array_unique(array_column($all, $this->id)), Base_Files::PLACE_CATEGORY, Base_Files_Types::IMAGE);

                    // Get ids
                    $ids = implode(",", array_column($all, $this->id));

                    // Get all the translations
                    $translations = $this->__linq->fromDB($this->table_translations_name)->whereDB("{$this->id} IN ($ids) ORDER BY IsValid DESC, IdLanguage ASC")->getResults();

                    $translations = $this->__linq->reorder($translations, $this->id, true);

                    // Cycle all data
                    foreach($all as $category) {

                        // Get the first translation
                        $translation = $translations->{$category->{$this->id}}[0];

                        // Create the obj for the response
                        $obj = new stdClass();
                        $obj->{$this->id} = $translation->{$this->id};
                        $obj->IsActive = $category->IsActive;
                        $obj->Title = $translation->Title;
                        $obj->Emoji = Base_Functions::IsNullOrEmpty($images) || !property_exists($images, $obj->{$this->id}) ? null : $images->{$obj->{$this->id}}[0]->FullPath;
                        $obj->Description = $translation->Description;
                        $obj->IdLanguages = array_column($translations->{$category->{$this->id}}, "IdLanguage");
                        $obj->IsDeletable = $all_deletable->{$obj->{$this->id}}->IsDeletable;
                        $obj->places_number = $this->countPlaces($translation->IdCategory);

                        array_push($response, $obj);
                    }
                }

                return $this->Success($response);
            }

            // Post
            public function create() {

                // Create the new row
                $id = $this->__opHelper->object($this->id)->table($this->table_name)->insertIncrement();

                // Check if the id is valid
                if (Base_Functions::IsNullOrEmpty($id))
                    return $this->Internal_Server_Error(null, "Qualcosa è andato storto!");

                return $this->Success($id);
            }
    
            // Put
            public function update() {

                $this->get($this->Request->{$this->id});

                if (!$this->Success)
                    return;

                // Get the request
                $request = $this->Request;
                // Get the translations
                $translations = $request->Languages;

                // Remove the languages from the request
                unset($request->Languages);

                // Create the obj to update
                $request->IsValid = 1;
                $this->__opHelper->object($request)->table($this->table_name)->where($this->id)->update();

                // Delete the translations
                $this->__opHelper->object($request)->table($this->table_translations_name)->where($this->id)->delete();

                $translations_values = array();

                // Cycle all languages
                foreach ($translations as $translation) {

                    $tmp = new stdClass();
                    $tmp->{$this->id} = $request->{$this->id};
                    $tmp->Title = $translation->Title;
                    $tmp->Description = $translation->Description;
                    $tmp->SlugUrl = Base_Functions::Slug($translation->Title);
                    $tmp->IdLanguage = $translation->IdLanguage;

                    // Convert the obj for the massive insert
                    $obj = Base_Functions::convertForMassive($tmp);

                    array_push($translations_values, "(" . implode(", ", array_values((array)$obj)) . ")");
                }

                // Insert the translations
                $this->__opHelper->table($this->table_translations_name)->insertMassive("(" . $this->id . ", Title, Description, SlugUrl, IdLanguage". ")", implode(", ", $translations_values));

                $this->refreshCache();
                return $this->Success(null, "Categoria salvata con successo!");
            }

            //Categories order
            public function updateCategoriesOrder() {

                // Cicle all the category
                foreach($this->Request->Order as $category) {

                    // Build the obj for the ordering 
                    $obj = new stdClass();
                    $obj->IdCategory = $category->IdCategory;
                    $obj->OrderNumber = $category->OrderNumber;
                    $this->__opHelper->object($obj)->table($this->table_name)->where("IdCategory")->update();

                }
                return $this->Success(false);

            }   

            // Delete 
            public function delete($idCategory) {

                $this->get($idCategory);

                if (!$this->Success)
                    return;

                // Create the obj to delete
                $obj = new stdClass();
                $obj->{$this->id} = $idCategory;
                $obj->IsDeleted = 1;

                $this->__opHelper->object($obj)->table($this->table_name)->where($this->id)->update();

                $this->refreshCache();
                return $this->Success(null, "Categoria eliminata con successo!");
            }

            #region Images

                public function getImage($idCategory) {

                    // Get
                    $this->get($idCategory);

                    // Check if exists
                    if (!$this->Success)
                        return $this->Not_Found(null, "Categoria non trovata!");    

                    // Get the image
                    $image = Base_File::getContentsManager($idCategory, Base_Files::PLACE_CATEGORY, Base_Files_Types::IMAGE);

                    return $this->Success(count($image) > 0 ? $image[0] : null);
                }

                public function uploadImage($idCategory) {

                    // Get
                    $image = $this->getImage($idCategory);

                    // Delete any existing image
                    if (!Base_Functions::IsNullOrEmpty($image))
                        Base_File::deleteContentManager($image->IdImage, Base_Files::PLACE_CATEGORY, Base_Files_Types::IMAGE);

                    // Save the new image
                    Base_File::saveContentManager($idCategory, Base_Files::PLACE_CATEGORY, Base_Files_Types::IMAGE);

                    $this->refreshCache();
                    return $this->Success(null, "Immagine caricata con successo!");
                }

            #endregion

        #endregion
            
        #region Private Methods

            private function formatTranslations($translations) {
                
                $response = array();

                // Cycle all the translations
                foreach($translations as $translation) {

                    // Create the obj
                    $language = new stdClass();
                    $language->IdLanguage = $translation->IdLanguage;
                    $language->Title = $translation->Title;
                    $language->Description = $translation->Description;

                    // Insert the obj
                    array_push($response, $language);
                }

                return $response;
            }
            private function checkDeletableCategories($ids) {

                $all = false;

                if (is_array($ids)) {

                    $ids = implode(",", $ids);
                    $all = true;
                }

                $sql = "SELECT 
                            cp.IdCategory, 
                            IF(COUNT(cpp.IdPlace) = 0, 1, 0) AS IsDeletable 
                        FROM categories_places cp
                        LEFT JOIN categories_places_parents cpp ON cp.IdCategory = cpp.IdCategory
                        LEFT JOIN places p ON cpp.IdPlace = p.IdPlace AND p.IsValid = 1 AND p.IsDeleted = 0
                        WHERE cp.IsValid = 1 AND cp.IsDeleted = 0 AND cp.IdCategory IN ($ids)
                        GROUP BY cp.IdCategory";

                $categories = $this->__linq->queryDB($sql)->getResults();

                // Check if null
                if (Base_Functions::IsNullOrEmpty($categories))
                    return true;

                if ($all)
                    return $this->__linq->reorder($categories, "IdCategory");
                else 
                    return $categories[0]->IsDeletable;
            }
            private function countPlaces($idCategory) {
                
                $sql = "SELECT 
                            COUNT(cpp.IdPlace) AS placesNumber 
                        FROM categories_places_parents cpp
                        LEFT JOIN places p ON p.IdPlace = cpp.IdPlace
                        WHERE p.IsValid = 1 AND p.IsDeleted = 0 AND cpp.IdCategory = $idCategory";

                $response = $this->__linq->queryDB($sql)->getFirstOrDefault();

                return $response->placesNumber;
            }

            private function refreshCache($reload_places = true) {

                if ($reload_places)
                    Base_Cache_Manager::setPlacesAllCache();

                Base_Cache_Manager::setPlacesCategoriesAll();
            }

        #endregion

    }

?>