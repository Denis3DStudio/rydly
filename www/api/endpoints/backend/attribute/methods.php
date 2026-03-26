<?php

    namespace Backend\Attribute;

    use stdClass;
    use Base_Methods;
    use Base_Functions;
    use Base_Attribute_Type;

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
            public function get($idAttribute) {

                // Check that the attibute exists
                $attribute = $this->__linq->fromDB("attributes")->whereDB("IdAttribute = $idAttribute AND IsDeleted = 0")->getFirstOrDefault();

                // Check that exists
                if (!Base_Functions::IsNullOrEmpty($attribute))
                    return $this->Success($this->formatAttribute($attribute));

                return $this->Not_Found();
            }
            public function getAll() {

                // Get all attributes
                $attributes = $this->__linq->fromDB("attributes")->whereDB("IsValid = 1 AND IsDeleted = 0 AND IsCustom = 0")->getResults();

                // Init the response array
                $response = array();

                // Check that the attributes is not null
                if (!Base_Functions::IsNullOrEmpty($attributes)) {

                    // Get the ids of the attributes
                    $ids_attributes = implode(", ", array_column($attributes, "IdAttribute"));

                    // Get content translations
                    $attributes_translations = $this->__linq->fromDB("attributes_translations")->whereDB("IdAttribute IN ($ids_attributes) ORDER BY IsValid DESC, IdLanguage ASC")->getResults();

                    // Cycle all attributes
                    foreach($attributes as $attribute) {

                        // Push the format data in the responsea array
                        array_push($response, $this->formatAttribute($attribute, $attributes_translations, true));
                    }
                }

                // Return the response
                return $this->Success($response);
            }
            
            // Post
            public function create() {

                // Create a new row in the attributes table
                $idAttribute = $this->__opHelper->object("IdAttribute")->table("attributes")->insertIncrement();

                // Check that is not null
                if (!Base_Functions::IsNullOrEmpty($idAttribute))
                    return $this->Success($idAttribute);

                return $this->Internal_Server_Error();
            }

            // Put
            public function update() {

                // Get the request
                $request = $this->Request;

                // Check if the attribute exists
                $this->get($request->IdAttribute);

                // Check if is success
                if ($this->Success == true) {

                    // Get the languages
                    $languages = $request->Languages;

                    // Remove Languages from request
                    unset($request->Languages);

                    // Add IsValid to 1
                    $request->IsValid = 1;
                    // Update the table
                    $this->__opHelper->object($request)->table("attributes")->where("IdAttribute")->update();

                    // Init the array for the translation values
                    $translation_values = array();

                    // Cycle all languages
                    foreach ($languages as $language) {
                        // Push the data in the translation array
                        array_push($translation_values, "($request->IdAttribute, '$language->Text', $language->IdLanguage, 1)");
                    }

                    // Delete the old translations
                    $sql = "DELETE FROM attributes_translations WHERE IdAttribute = $request->IdAttribute";
                    $this->__linq->queryDB($sql)->getResults();

                    // Insert the languages
                    $this->__opHelper->table("attributes_translations")->insertMassive("(IdAttribute, Text, IdLanguage, IsValid)", implode(", ", $translation_values));

                    // Check if the attribute Type
                    if ($request->Type != Base_Attribute_Type::COLOR) {

                        // Get all the attribute values
                        $attributes_values = array_column($this->__linq->fromDB("attributes_values")->whereDB("IdAttribute = $request->IdAttribute AND IsValid = 1 AND IsDeleted = 0")->getResults(), "IdAttributeValue");

                        // Cycle all the attribute values
                        foreach ($attributes_values as $idAttributeValue) {
                            // Delete all the colors
                            $this->attribute_value->deleteAllColors($idAttributeValue);
                        }
                    }

                    return $this->Success();
                }

                return $this->Not_Found();
            }
            
            // Delete
            public function delete($idAttribute) {

                // Check if the attribute exists
                $attribute = $this->get($idAttribute);

                // Check if is success
                if ($this->Success == true) {

                    // Check if the attribute can been deleted
                    if ($attribute->CanDelete == true) {

                        // Create the obj to delete
                        $obj = new stdClass();
                        $obj->IdAttribute = $idAttribute;
                        $obj->IsDeleted = 1;

                        // Update the attributes table
                        $this->__opHelper->object($obj)->table("attributes")->where("IdAttribute")->update();

                        $this->deleteAll($idAttribute);

                        return $this->Success();
                    }
                }   

                $this->Not_Found();
            }

        #endregion
            
        #region Private Methods

            private function formatAttribute($attribute, $attributes_translations = null, $getAll = false) {

                // Get content translations
                if($attributes_translations == null)
                    $attributes_translations = $this->__linq->fromDB("attributes_translations")->whereDB("IdAttribute = $attribute->IdAttribute")->getResults();
            
                // Reorder
                $attributes_translations = $this->__linq->reorder($attributes_translations, "IdAttribute", true);

                // Build object
                $response = new stdClass();
                $response->IdAttribute = $attribute->IdAttribute;
                $response->Date = $attribute->InsertDate;
                $response->CanDelete = true;
                $response->Type = $attribute->Type;
                $response->LanguagesIds = array();
                
                if (!$getAll)
                    $response->Languages = array();
                
                // Check translations
                if(property_exists($attributes_translations, $attribute->IdAttribute)) {

                    if ($getAll == true) {

                        // Get translations
                        $response->Text = $attributes_translations->{$attribute->IdAttribute}[0]->Text;
                        $response->LanguagesIds = array_column($attributes_translations->{$attribute->IdAttribute}, "IdLanguage");
                    }   
                    else {

                        // Get translations
                        $attributes_translations = $attributes_translations->{$attribute->IdAttribute};

                        foreach ($attributes_translations as $translation) {

                            // Create the obj of the translation
                            $obj = new stdClass();
                            $obj->Text = $translation->Text;
                            $obj->IsValid = $translation->IsValid;
                            $obj->IdLanguage = $translation->IdLanguage;
                            
                            // Push the obj in the languages array
                            array_push($response->Languages, $obj);
                        }
                    }
                }

                // Merge the the 
                $response = Base_Functions::mergeObjects($response, $this->formatEditData($attribute->IdCreator, $attribute->IdModifier, $attribute->InsertDate, $attribute->UpdateDate));

                return $response;
            }
            private function formatEditData($idCreator, $idModifier, $insertDate, $updateDate) {

                // Check if the idModifier is null
                if (Base_Functions::IsNullOrEmpty($idModifier))
                    $idModifier = $idCreator;
                // Check if the updateDate is null
                if (Base_Functions::IsNullOrEmpty($updateDate))
                    $updateDate = $insertDate;

                // Check if the idCreator = $idModifier
                $account_where = ($idCreator == $idModifier) ? "= $idCreator" : "IN ($idCreator, $idModifier)";

                // Get accounts
                $accounts = $this->__linq->reorder($this->__linq->fromDB("accounts")->whereDB("IdAccount $account_where")->getResults(), "IdAccount");

                // Create the response obj
                $response = new stdClass();
                $response->Creator = $accounts->{$idCreator}->Name . " " . $accounts->{$idCreator}->Surname;
                $response->Modifier = $accounts->{$idModifier}->Name . " " . $accounts->{$idModifier}->Surname;
                $response->InsertDate = Base_Functions::FormatDate("d/m/Y H:i", $insertDate);
                $response->UpdateDate = Base_Functions::FormatDate("d/m/Y H:i", $updateDate);

                return $response;
            }

            private function deleteAll($idAttribute) {

                // Get all attribute value ids
                $attribute_values_ids = array_column($this->__linq->fromDB("attributes_values")->whereDB("IdAttribute = $idAttribute")->getResults(), "IdAttributeValue");

                // Check that the attribute_values_ids is not null
                if (!Base_Functions::IsNullOrEmpty($attribute_values_ids)) {

                    $attribute_values_ids = implode(", ", $attribute_values_ids);

                    // Create the query to delete the value of the attribute
                    $sql = "DELETE FROM attributes_values WHERE IdAttributeValue IN ($attribute_values_ids)";
                    $this->__linq->queryDB($sql)->getResults();

                    // Create the query to delete the translation value of the attribute
                    $sql = "DELETE FROM attributes_values_translations WHERE IdAttributeValue IN ($attribute_values_ids)";
                    $this->__linq->queryDB($sql)->getResults();

                    return true;
                }
            }

        #endregion

    }

?>