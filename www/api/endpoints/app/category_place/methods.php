<?php

    namespace App\Category_Place;

    use stdClass;
    use Base_Methods;
    use Base_Functions;
    use Base_Cache_Names;
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

                $idLanguage = $this->Logged->IdLanguage;

                // Check if the exists and is not null
                $sql = "SELECT tt.*, t.OrderNumber, t.Emoji
                        FROM {$this->table_name} t
                        INNER JOIN {$this->table_translations_name} tt ON tt.{$this->id} = t.{$this->id}
                        WHERE t.IsActive = 1 AND t.IsValid = 1 AND t.IsDeleted = 0 AND t.IdCategory  = $idCategory AND tt.IdLanguage = $idLanguage
                        ORDER BY t.OrderNumber ASC";
                $data = $this->__linq->queryDB($sql)->getFirstOrDefault();

                // Check if the data is null
                if (Base_Functions::IsNullOrEmpty($data))
                    return $this->Not_Found(null, "Categoria non trovata!");

                $response = new stdClass();
                $response->Title = $data->Title;
                $response->Icon = $data->Emoji;
                $response->PlacesCount = $this->countPlaces($idCategory);

                return $this->Success($response);
            }
            public function getAll() {

                $idLanguage = $this->Logged->IdLanguage;

                $cache_categories = Base_Cache_Manager::getCache(Base_Cache_Names::PLACES_CATEGORIES_ALL);
                $ids_categories = array_keys((array)$cache_categories);

                $sql = "SELECT cpp.IdCategory, COUNT(*) AS PlaceNumber
                        FROM categories_places_parents cpp
                        INNER JOIN places p ON cpp.IdPlace = p.IdPlace AND p.IsValid = 1 AND p.IsActive = 1 AND p.IsDeleted = 0
                        INNER JOIN places_translations pt ON p.IdPlace = pt.IdPlace AND pt.IdLanguage = $idLanguage
                        WHERE cpp.IdCategory IN (" . implode(",", $ids_categories) . ")
                        GROUP BY cpp.IdCategory";
                
                $places_number = $this->__linq->reorder($this->__linq->queryDB($sql)->getResults(), "IdCategory");

                $categories = array();

                // Cycle all categories and add the number of places for each category
                foreach ($cache_categories as $idCategory => $category) {

                    // Check if the category has the translation in the current language
                    if (property_exists($category->Translations, $idLanguage)) {

                        $category->Name = $category->Translations->{$idLanguage};
                        $category->Icon = $category->FullPath;
                        $category->PlacesCount = property_exists($places_number, $idCategory) ? $places_number->{$idCategory}->PlaceNumber : 0;

                        $temp = (clone $category);
                        unset($temp->Translations);
                        unset($temp->FullPath);

                        array_push($categories, $temp);
                    }
                }

                return $this->Success($categories);
            }           

        #endregion

        #region Private Methods

        private function countPlaces($idCategory) {
                
            $sql = "SELECT 
                        COUNT(cpp.IdPlace) AS placesNumber 
                    FROM categories_places_parents cpp
                    LEFT JOIN places p ON p.IdPlace = cpp.IdPlace
                    WHERE p.IsActive = 1 AND p.IsValid = 1 AND p.IsDeleted = 0 AND cpp.IdCategory = $idCategory";

            $response = $this->__linq->queryDB($sql)->getFirstOrDefault();

            return $response->placesNumber;
        }

        #endregion
    }

?>