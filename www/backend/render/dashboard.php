<?php

    namespace Render\Dashboard;

    use stdClass;
    use Base_Render;

    class Methods extends Base_Render {

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }
        #endregion

        #region Public Methods

            public function index() {

                $response = new stdClass();
                $response->News = $this->getNewsData();
                $response->Products = $this->getProductsData();

                return $response;
            }

        #endregion

        #region Private Methods

            // Get
            private function getNewsData() {

                // Get the news count
                $news_count = count($this->__linq->fromDB("news")->whereDB("IsValid = 1 AND IsDeleted = 0")->getResults());
                // Get the news categories count
                $news_categories = count($this->__linq->fromDB("categories")->whereDB("IsValid = 1 AND IsDeleted = 0")->getResults());

                return (object) array(
                    "Count" => $news_count,
                    "CategoriesCount" => $news_categories
                );
            }
            private function getProductsData() {

                // Get the products count
                $products_count = count($this->__linq->fromDB("products")->whereDB("IsValid = 1 AND IsDeleted = 0")->getResults());
                // Get the products categories count
                $products_categories = count($this->__linq->fromDB("categories_products")->whereDB("IsValid = 1 AND IsDeleted = 0")->getResults());
                // Get the products attributes count
                $products_attributes = count($this->__linq->fromDB("attributes")->whereDB("IsValid = 1 AND IsDeleted = 0")->getResults());

                return (object) array(
                    "Count" => $products_count,
                    "CategoriesCount" => $products_categories,
                    "AttributesCount" => $products_attributes
                );
            }

        #endregion

    }

?>