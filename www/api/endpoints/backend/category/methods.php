<?php

    namespace Backend\Category;

    use Base_Category_Type;
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
        private $table_refs_name = "refs_categories";

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
            public function getAll($idType = null, $ids = []) {

                // Check if $ids is not an array and it's not null, if it's the case, create an array with the single id
                $returnDirect = is_string($ids) && !Base_Functions::IsNullOrEmpty($ids);

                // Set ids as array if it's not an array and it's not null
                if (!is_array($ids) && !Base_Functions::IsNullOrEmpty($ids))
                    $ids = [$ids];

                // Check if reorder by ref
                $reorderByRef = !Base_Functions::IsNullOrEmpty($ids);

                // Base where condition
                $where = "t.IsValid = 1 AND t.IsDeleted = 0";

                // Add the IdType filter if it's not null
                $where .= !Base_Functions::IsNullOrEmpty($idType) ? " AND t.IdType = $idType" : "";

                // Create the ids filter if $ids is not null
                $inner = "";
                if ($reorderByRef)
                    $inner = "INNER JOIN {$this->table_refs_name} tr ON t.IdCategory = tr.IdCategory AND tr.ContentRefId IN (" . implode(", ", array_map('intval', $ids)) . ") AND tr.IdType = $idType";

                // Build the sql
                $sql = "SELECT DISTINCT t.*
                        FROM {$this->table_name} t
                        $inner
                        WHERE $where";

                // Get all data
                $categories = $this->__linq->queryDB($sql)->getResults();

                // Delete duplicates if $reorderByRef is true
                if ($reorderByRef) {

                    // Get unique categories by id
                    $ids_categories = array_column($categories, $this->id);

                    // Build the real categories array with unique values
                    $categories = array_map(function($id) use ($categories) {
                        return $categories[array_search($id, array_column($categories, $this->id))];
                    }, $ids_categories);
                }

                // Get all refs categories
                $refs_categories = $reorderByRef ? $this->__linq->reorder($this->__linq->fromDB($this->table_refs_name)->whereDB("IdType = $idType AND ContentRefId IN (" . implode(", ", array_map('intval', $ids)) . ")")->getResults(), "ContentRefId", true) : new stdClass();

                // Check that $all is not null
                if (Base_Functions::IsNullOrEmpty($categories))
                    return $this->Success([]);

                // Get all the translations for the categories
                $translations = $this->__linq->reorder($this->__linq->fromDB($this->table_translations_name)->whereDB("IdCategory IN (" . implode(", ", array_column($categories, $this->id)) . ")")->getResults(), "IdCategory");

                // Get all the deletable categories
                $deletable_categories = $this->checkDeletableCategories(array_column($categories, $this->id), $idType);

                // Create the response array
                $tmpCategories = [];

                // Cycle all data
                foreach($categories as $category) {

                    // Get the translations for the current category
                    $translation_category = $this->__linq->reorder($translations->{$category->{$this->id}}, "IdLanguage") ?? null;

                    // Get the current languagge translation
                    $translation = $translation_category->{$this->Logged->IdLanguage} ?? null;

                    // Create the obj for the response
                    $obj = new stdClass();
                    $obj->{$this->id} = $translation->{$this->id};
                    $obj->Title = $translation->Title;
                    $obj->Description = $translation->Description;

                    // Add additional properties
                    if (!$reorderByRef) {
                        $obj->IdLanguages = array_column((array)$translation_category, "IdLanguage");
                        $obj->IsDeletable = property_exists($deletable_categories, $obj->{$this->id}) ? $deletable_categories->{$obj->{$this->id}}->IsDeletable : false;
                    }

                    array_push($tmpCategories, $obj);
                }

                // If not reorder by ref, return the response
                if (!$reorderByRef)
                    return $this->Success($tmpCategories);

                // Init the response array
                $response = new stdClass();

                // Cicle all the refs categories to reorder the response
                foreach ($refs_categories as $refId => $ref_categories) {

                    $response->$refId = [];

                    // Get all the ids of the current ref
                    $ids_ref_category = array_column($ref_categories, $this->id);

                    // Get the current categories in the response
                    $current_categories = array_filter($tmpCategories, function($item) use ($ids_ref_category) {
                        return in_array($item->{$this->id}, $ids_ref_category);
                    });

                    // Push if the current category is Main or not
                    $current_categories = array_map(function($category) use ($ref_categories) {
                        $category->IsMain = in_array($category->{$this->id}, array_column($ref_categories, $this->id));
                        return $category;
                    }, $current_categories);

                    // Push the current categories in the response
                    $response->$refId = array_values($current_categories);
                }

                // Get the return id
                $returnId = $returnDirect ? $ids[0] : null;

                // Return the response
                return $this->Success($returnDirect ? (property_exists($response, $returnId) ? $response->{$returnId} : []) : $response);
            }

            // Post
            public function create() {

                // Create the new row
                $id = $this->__opHelper->object($this->id)->table($this->table_name)->insertIncrement();

                // Check if the id is valid
                if (Base_Functions::IsNullOrEmpty($id))
                    return $this->Internal_Server_Error(null, "Qualcosa è andato storto!");

                // Get the request
                $request = $this->Request;

                // Add the id to the request
                $request->{$this->id} = $id;

                // Update the new row with the id
                $this->__opHelper->object($request)->table($this->table_name)->where($this->id)->update();

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
                    $tmp->IsValid = 1;

                    // Convert the obj for the massive insert
                    $obj = Base_Functions::convertForMassive($tmp);

                    array_push($translations_values, "(" . implode(", ", array_values((array)$obj)) . ")");
                }

                // Insert the translations
                $this->__opHelper->table($this->table_translations_name)->insertMassive("(" . $this->id . ", Title, Description, SlugUrl, IdLanguage, IsValid)", implode(", ", $translations_values));

                return $this->Success(null, "Categoria salvata con successo!");
            }
            public function updateByType($idType, $idRef, $newIds, $mainId = null) {

                // Delete the old refs
                $sql = "DELETE FROM $this->table_refs_name WHERE IdType = $idType AND ContentRefId = $idRef";
                $this->__linq->queryDB($sql)->getResults();

                // Check if there are new refs to insert
                if (Base_Functions::IsNullOrEmpty($newIds) || in_array("-1", $newIds))
                    return $this->Success();

                // Init
                $massive = [];

                // Insert the new refs
                foreach ($newIds as $id) {

                    $ref_obj = new stdClass();
                    $ref_obj->{$this->id} = $id;
                    $ref_obj->ContentRefId = $idRef;
                    $ref_obj->IdType = $idType;
                    $ref_obj->IsMain = ($mainId == $id) ? 1 : 0;

                    $massive[] = "(" . implode(", ", array_values((array)$ref_obj)) . ")";
                }

                // Insert the new refs
                $this->__opHelper->table($this->table_refs_name)->insertMassive("($this->id, ContentRefId, IdType, IsMain)", implode(", ", $massive));

                return $this->Success();
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
            private function checkDeletableCategories($ids = [], $type = null) {

                // Check if $ids in not null and $type is not null
                if (Base_Functions::IsNullOrEmpty($ids) || Base_Functions::IsNullOrEmpty($type))
                    return new stdClass();

                // Create the where condition for the type
                $all = false;

                // Check if $ids is an array, if it's not, create an array with the single id
                if (is_array($ids)) {

                    $ids = implode(",", $ids);
                    $all = true;
                }

                // Get the inner table name based on the type
                $id_join = Base_Category_Type::IDS_TO_JOIN[$type] ?? null;
                $main_table_inner = Base_Category_Type::MAIN_TABLE_INNER[$type] ?? null;

                $sql = "SELECT c.$this->id, IF(COUNT(cn.ContentRefId) = 0, 1, 0) AS IsDeletable 
                        FROM $this->table_name c
                        LEFT JOIN $this->table_refs_name cn ON c.$this->id = cn.$this->id AND cn.IdType = $type
                        LEFT JOIN $main_table_inner mt ON cn.ContentRefId = mt.$id_join AND mt.IsValid = 1 AND mt.IsDeleted = 0
                        WHERE c.IsValid = 1 AND c.IsDeleted = 0 AND c.$this->id IN ($ids)
                        GROUP BY c.$this->id";

                $categories = $this->__linq->queryDB($sql)->getResults();

                if ($all)
                    return $this->__linq->reorder($categories, "$this->id");
                else 
                    return $categories[0]->IsDeletable;
            }

        #endregion

    }

?>