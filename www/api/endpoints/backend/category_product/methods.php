<?php

    namespace Backend\Category_Product;

    use stdClass;
    use Base_Functions;
    use Base_Methods;

    class Methods extends Base_Methods {

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }      
        #endregion
        
        #region Public Methods

            // Get
            public function get() {

                // Get all the Category 
                $category = $this->__linq->fromDB("categories_products_translations")->whereDB("IdCategory = {$this->Request->IdCategory}")->getResults();;

                // Get the id category of the products
                $arrayCategoryNews = $this->getCategoryProduct();

                // Build the response
                $response = new stdClass();
                $response->Languages =$this->formatLanguages($category);
                $response->Dates = $this->getChangesDates();
                $response->Delete = false;

                if(in_array($this->Request->IdCategory, $arrayCategoryNews)) 
                    $response->Delete = true;

                if(isset($this->Request->Verbose)) 
                    $response->Accounts = $this->getAccounts();
                    

                return $this->Success($response);

            }
            public function getAll() {

                // Get all the Category 
                $sql = 'SELECT cpt.Name, cp.IdCategory, cp.IdParent as IdCategoryParent, cp.Position
                        FROM categories_products cp 
                        INNER JOIN categories_products_translations cpt ON cp.IdCategory = cpt.IdCategory AND cp.IsValid = 1 AND cpt.IdLanguage = 1';
            
                $categories = $this->__linq->queryDB($sql)->getResults();
            
                foreach($categories as $category) {
                    $childs = $this->__linq->fromDB("categories_products")->whereDB("IdParent = $category->IdCategory AND IsValid = 1")->getResults();

                    if (count($childs) > 0) 
                        $category->HasChilds = true;
                    else
                        $category->HasChilds = false;
                    
                }
                
                return $this->Success($categories);
            }
            public function getSelect() {

                $id_language = $this->Logged->IdLanguage;

                $sql = "SELECT cp.IdCategory, IFNULL(cp.IdParent, -1) AS IdParent, cpt.Name
                        FROM categories_products cp
                        INNER JOIN categories_products_translations cpt ON cp.IdCategory = cpt.IdCategory AND cpt.IdLanguage = $id_language
                        WHERE cp.IsValid = 1 AND cp.IsDeleted = 0";

                $categories = $this->__linq->queryDB($sql)->getResults();

                $response = array();

                // Check if the category is not null
                if (count($categories) > 0) {

                    // Reorder the categories by IdParent
                    $categories_reordered = $this->__linq->reorder($categories, "IdParent", true);

                    // Get the categories parents and remove them from the reordered array
                    $categories_parents = $this->__linq->reorder($categories_reordered->{-1}, "IdCategory");
                    unset($categories_reordered->{-1});

                    // Cycle all reordered categories
                    foreach ($categories_reordered as $id_parent => $categories) {

                        // Create the obj
                        $obj = new stdClass();
                        $obj->IdCategoryParent = $id_parent;
                        $obj->CategoryParentName = $categories_parents->{$id_parent}->Name;

                        $obj->Childs = array();

                        // Check if the category has childs
                        if (count($categories) > 0) {

                            // Remove the IdParent attribute from the categories
                            foreach ($categories as $category) {
                                unset($category->IdParent);
                                array_push($obj->Childs, $category);
                            }
                        }

                        array_push($response, $obj);
                    }
                }

                return $this->Success($response);
            }
    
            // Post
            public function create() {
                
                $idCategory = $this->__opHelper->object("IdCategory")->table("categories_products")->insertIncrement();
                unset($this->Request->update_date);
                $this->Request->IdCategory = $idCategory;

                if(Base_Functions::IsNullOrEmpty($this->Request->IdParent))
                    $this->Request->Position = 1;
                else 
                    $this->Request->Position = $this->__linq->selectDB('Position')->fromDB("categories_products")->whereDB("IdCategory = {$this->Request->IdParent}")->getFirstOrDefault()->Position + 1;

                $this->__opHelper->object($this->Request)->table("categories_products")->where("IdCategory")->update();

                if(is_numeric($idCategory) && $idCategory > 0)
                    return $this->Success($idCategory);

                return $this->Internal_Server_Error();

            }

            // Put
            public function update() {

                $checkValidity = new stdClass();
                $checkValidity = true;
                $duplicates = [];
                $languagesErrorTab = [];
                $date = date('Y-m-d H:i:s');

                // Cicle all the languages
                foreach($this->Request->Languages as $language) {
                    
                    // Check if to modify
                    if(filter_var($language->Update, FILTER_VALIDATE_BOOLEAN)) {

                        // Build the response
                        $language->IdCategory = intval($this->Request->IdCategory);
                        $idTranslation = $this->__linq->selectDB('IdTranslation')->fromDB("categories_products_translations")->whereDB("(IdCategory = $language->IdCategory AND IdLanguage = $language->IdLanguage)")->getFirstOrDefault();
                        $condExistingAndSame = '';

                        // Check if translation exist
                        if(!Base_Functions::IsNullOrEmpty($idTranslation))
                            $condExistingAndSame = "AND IdCategory != $language->IdCategory";


                        #region check Duplicates

                            $condDuplicates = '';

                            foreach ($this->Request->Duplicates as $duplicate) {

                                // Build the cond
                                $condDuplicates .= $duplicate . ' LIKE ' . "'" . $language->{$duplicate} . "'";
                                
                                // Check if the Name already exists and add to errors
                                if(!Base_Functions::IsNullOrEmpty($this->__linq->fromDB("categories_products_translations")->whereDB($condDuplicates . " AND IdLanguage = $language->IdLanguage $condExistingAndSame")->getFirstOrDefault())) {
                                    
                                    $checkValidity = false;
                                    $response = new stdClass();
                                    $response->IdInput = '#' . $duplicate . '-' . $language->IdLanguage;

                                    array_push($languagesErrorTab, $language->IdLanguage);
                                    array_push($duplicates, $response);

                                } 

                            }

                        #endregion              
                        
                    }

                }

                #region return

                    // Return the response
                    if($checkValidity) {

                        // Cicles the languages to insert all the categories
                        foreach($this->Request->Languages as $language) {
                            
                            // Check if to modify
                            $language->IdCategory = intval($this->Request->IdCategory);
                            $idTranslation = $this->__linq->selectDB('IdTranslation')->fromDB("categories_products_translations")->whereDB("(IdCategory = $language->IdCategory AND IdLanguage = $language->IdLanguage)")->getFirstOrDefault();
                            
                            $obj = new stdClass();
                            $obj->IsValid = 1;
                            $slugUrl = Base_Functions::Slug($language->Name);
                            $obj->IdModifier = $this->Logged->IdAccount;

                            if(Base_Functions::IsNullOrEmpty($idTranslation)) {

                                // Bulid the insert
                                $language->IsValid = 1;
                                $language->IsDeleted = 0;
                                $language->SlugUrl = $slugUrl;
                                unset($language->Update);

                                $id = $this->__opHelper->object($language)->table("categories_products_translations")->insert();

                                $obj->IdCategory = $id;

                                $sql = "UPDATE categories_products
                                        SET IsValid = 1, IsDeleted = 0, UpdateDate = '$date', IdModifier = '{$this->Logged->IdAccount}'
                                        WHERE IdCategory = " . $language->IdCategory;
                                $this->__linq->queryDB($sql)->getFirstOrDefault();

                                
                            } else {

                                // Update the already existing
                                $sql = "UPDATE categories_products_translations
                                        SET Name = '$language->Name', Description = '$language->Description', SlugUrl = '$slugUrl', IsValid = 1, IsDeleted = 0
                                        WHERE IdTranslation = " . $idTranslation->IdTranslation;

                                $this->__linq->queryDB($sql)->getFirstOrDefault();

                                // Update valid on categories
                                $sql = "UPDATE categories_products
                                        SET IsValid = 1, IsDeleted = 0, UpdateDate = '$date', IdModifier = '{$this->Logged->IdAccount}'
                                        WHERE IdCategory = " . $language->IdCategory;
                                $this->__linq->queryDB($sql)->getFirstOrDefault();

                            }
                            


                        }

                        return $this->Success();

                    } else {

                        // Build the response
                        $response = new stdClass();
                        $response->Languages = $languagesErrorTab;
                        $response->Duplicates = $duplicates;

                        return $this->Not_Found($response);
                    }

                #endregion
            
            }

            // Delete
            public function delete() {
                
                $this->Request->IsValid = 0;
                $this->Request->IdDeleted = 1;

                $idParent = $this->__linq->fromDB("categories_products")->whereDB("IdCategory = {$this->Request->IdCategory}")->getFirstOrDefault()->IdParent;
                if(Base_Functions::IsNullOrEmpty($this->__opHelper->object($this->Request)->table("categories_products")->where("IdCategory")->delete()))
                    return $this->Success($idParent);

                return $this->Not_Found();

            }

        #endregion
            
        #region Private Methods

            private function formatLanguages($categoryTranslations) {
                $response = [];
                foreach($categoryTranslations as $translation) {
                    $language = new stdClass();
                    $language->IdTranslationLanguage = $translation->IdLanguage;
                    $language->IdLanguage = $translation->IdLanguage;
                    $language->Name = $translation->Name;
                    $language->Description = $translation->Description;
                    array_push($response, $language);
                }
                return $response;
            } 
            private function getAccounts() {

                $accounts = new stdClass();
                
                // Get the ids of the accounts of the category
                $idsAccounts = $this->__linq->selectDB('IdCreator, IdModifier')->fromDB("categories_products")->whereDB("IdCategory = {$this->Request->IdCategory}")->getFirstOrDefault();

                // Get the singles accounts
                if(!Base_Functions::IsNullOrEmpty($idsAccounts->IdCreator)) {
                    $accounts->CreatorName = $this->__linq->selectDB('Name, Surname')->fromDB("accounts")->whereDB("IdAccount = $idsAccounts->IdCreator")->getFirstOrDefault();
                    $accounts->CreatorName = $accounts->CreatorName->Name . ' ' . $accounts->CreatorName->Surname;
                }
                if(!Base_Functions::IsNullOrEmpty($idsAccounts->IdModifier))
                    $accounts->ModifierName = $this->__linq->selectDB('Name, Surname')->fromDB("accounts")->whereDB("IdAccount = $idsAccounts->IdModifier")->getFirstOrDefault();
                    $accounts->ModifierName = $accounts->ModifierName->Name . ' ' . $accounts->ModifierName->Surname;

                
                return $accounts;
            }
            private function getChangesDates() {
                $dates = $this->__linq->selectDB('InsertDate, UpdateDate')->fromDB("categories_products")->whereDB("IdCategory = {$this->Request->IdCategory}")->getFirstOrDefault();
                foreach($dates as $key => $date) {
                    $dates->{$key} = Base_Functions::FormatDate('d/m/Y H:i', $date);
                }
                return $dates;
            }
            private function getCategoryProduct() {

                $sql = "SELECT cp.IdCategory
                        FROM categories_products cp
                        INNER JOIN products_categories pc ON cp.IdCategory = pc.IdCategory
                        WHERE cp.IsValid = 1 AND cp.IsDeleted = 0";

                return array_column($this->__linq->queryDB($sql)->getResults(), "IdCategory");
            }

        #endregion

    }

?>