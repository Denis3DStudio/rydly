<?php

    namespace Backend\Category;

    use stdClass;
    use Base_File;
    use Base_Files;
    use Base_Methods;
    use Base_Functions;
    use Base_Files_Types;

    class Methods extends Base_Methods {

        private $id = "IdCategory";
        private $table_name = "categories";
        private $table_translations_name = "categories_translations";
        private $table_images_name = "categories_images";

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
                $sql = "SELECT DISTINCT t.{$this->id}, t.Color
                        FROM {$this->table_name} t
                        INNER JOIN {$this->table_translations_name} tt ON tt.{$this->id} = t.{$this->id}
                        WHERE t.IsValid = 1 AND t.IsDeleted = 0";

                $all = $this->__linq->queryDB($sql)->getResults();

                $response = array();

                // Check that $all is not null
                if (count($all) > 0) {

                    $all_deletable = $this->checkDeletableCategories(array_unique(array_column($all, $this->id)));

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
                        $obj->Title = $translation->Title;
                        $obj->Color = $category->Color;
                        $obj->IdLanguages = array_column($translations->{$category->{$this->id}}, "IdLanguage");
                        $obj->IsDeletable = $all_deletable->{$obj->{$this->id}}->IsDeletable;

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

                return $this->Success(null, "Categoria salvata con successo!");
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

                return $this->Success(null, "Categoria eliminata con successo!");
            }

            #region Images

                // Get
                public function getImages($idCategory) {

                    // Get
                    $this->get($idCategory);

                    // Check if exists
                    if (!$this->Success)
                        return ;

                    // Get the images
                    $images = $this->__linq->fromDB($this->table_images_name)->whereDB("{$this->id} = $idCategory ORDER BY OrderNumber ASC")->getResults();

                    return $this->Success($images);
                }

                // Post
                public function uploadImages($idCategory, $identifier) {

                    // Get
                    $this->get($idCategory);

                    // Check if exists
                    if (!$this->Success)
                        return;

                    // Get the folder path
                    $folder_path = $this->chunk->getFolderPath($identifier);
                    $files = glob(OFF_ROOT . "$folder_path*");

                    // Save the images
                    Base_File::saveContentManager($idCategory, Base_Files::CATEGORY, Base_Files_Types::IMAGE, [], $files);

                    // Remove from chunk folder
                    $this->chunk->deleteAll($identifier);

                    return $this->Success(null, "Immagini caricate con successo!");
                }

                // Put
                public function updateImagesOrder() {

                    // Get the request
                    $request = $this->Request;

                    // Get
                    $this->get($request->{$this->id});

                    // Check if exists
                    if (!$this->Success)
                        return;
    
                    // Cycle the Order array
                    foreach($request->Order as $img) {

                        // Create the update object
                        $obj = new stdClass();
                        $obj->IdImage = $img->id;
                        $obj->OrderNumber = $img->order_number;

                        // Update image table
                        $this->__opHelper->object($obj)->table($this->table_images_name)->where("IdImage")->update();
                    }

                    return $this->Success(null, "Ordine delle immagini aggiornato con successo!");
                }   
                
                // Delete
                public function deleteImage($idCategory, $idImage) {

                    // Get
                    $this->get($idCategory);

                    // Check if exists
                    if (!$this->Success)
                        return;

                    // Get the image
                    $image = Base_File::getContentManager($idImage, Base_Files::CATEGORY, Base_Files_Types::IMAGE);

                    // Check if the image exists
                    if (!Base_Functions::IsNullOrEmpty($image)) {

                        // Delete the image
                        Base_File::deleteContentManager($idImage, Base_Files::CATEGORY, Base_Files_Types::IMAGE);

                        return $this->Success(null, "Immagine eliminata con successo!");
                    }

                    return $this->Not_Found(null, "Immagine non trovata!");
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
                            c.IdCategory, 
                            IF(COUNT(cn.IdNews) = 0, 1, 0) AS IsDeletable 
                        FROM categories c
                        LEFT JOIN categories_news cn ON c.IdCategory = cn.IdCategory
                        LEFT JOIN news n ON cn.IdNews = n.IdNews AND n.IsValid = 1 AND n.IsDeleted = 0
                        WHERE c.IsValid = 1 AND c.IsDeleted = 0 AND c.IdCategory IN ($ids)
                        GROUP BY c.IdCategory";

                $categories = $this->__linq->queryDB($sql)->getResults();

                // Check if null
                if (Base_Functions::IsNullOrEmpty($categories))
                    return true;

                if ($all)
                    return $this->__linq->reorder($categories, "IdCategory");
                else 
                    return $categories[0]->IsDeletable;
            }

        #endregion

    }

?>