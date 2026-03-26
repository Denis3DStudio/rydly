<?php

    namespace App\Category;

    use stdClass;
    use Base_Methods;
    use Base_Functions;

    class Methods extends Base_Methods {

        private $id = "IdCategory";
        private $table_name = "categories";
        private $table_translations_name = "categories_translations";

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }      
        #endregion
        
        #region Public Methods

            // Get
            public function getAll() {

                $idLanguage = $this->Logged->IdLanguage;
                // Get all data
                $sql = "SELECT t.IdCategory, t.Color, tt.IdTranslation, tt.IdLanguage, tt.Title, tt.SlugUrl
                        FROM {$this->table_name} t
                        INNER JOIN {$this->table_translations_name} tt ON tt.{$this->id} = t.{$this->id} AND tt.IdLanguage = {$idLanguage}
                        WHERE t.IsValid = 1 AND t.IsDeleted = 0 AND EXISTS (SELECT 1
                                                                            FROM categories_news cn
                                                                            JOIN news n ON n.IdNews = cn.IdNews AND n.IsValid = 1 AND n.IsDeleted = 0 AND n.Status = 1
                                                                            WHERE cn.IdCategory = t.IdCategory)
                        ORDER BY tt.Title ASC";

                $all = $this->__linq->queryDB($sql)->getResults();

                $response = array();

                // Check that $all is not null
                if (count($all) > 0) {

                    // cycle data
                    foreach($all as $category) {
                        
                        // Create the obj for the response
                        $obj = new stdClass();
                        $obj->{$this->id} = $category->{$this->id};
                        $obj->Title = $category->Title;
                        $obj->Color = $category->Color;

                        array_push($response, $obj);
                    }
                }

                return $this->Success($response);
            }
            
        #endregion
            
        #region Private Methods
        #endregion

    }

?>