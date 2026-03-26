<?php

    namespace Backend\Attribute_Value;

    use stdClass;
    use Base_Methods;
    use Base_Functions;
    use Base_Attribute_Type;
    use Base_Attribute_Value_Color;

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
            public function get($idAttribute, $idAttributeValue) {

                // Check that the attribute exists
                if ($this->checkAttribute() == true) {

                    // Check that the attibute exists
                    $attribute_value = $this->__linq->fromDB("attributes_values")->whereDB("IdAttributeValue = $idAttributeValue AND IdAttribute = $idAttribute AND IsDeleted = 0")->getFirstOrDefault();
    
                    // Check that exists
                    if (!Base_Functions::IsNullOrEmpty($attribute_value))
                        return $this->Success($this->formatAttribute($attribute_value));
                }

                return $this->Not_Found();
            }
            public function getAll($idAttribute) {

                // Check if the idAttribute is valid
                if ($this->checkAttribute() == true) {

                    // Get all attributes values
                    $attributes = $this->__linq->fromDB("attributes_values")->whereDB("IdAttribute = $idAttribute AND IsValid = 1 AND IsDeleted = 0")->getResults();
    
                    // Init the response array
                    $response = array();
    
                    // Check that the attributes is not null
                    if (!Base_Functions::IsNullOrEmpty($attributes)) {
    
                        // Get the ids of the attributes
                        $ids_attributes = implode(", ", array_column($attributes, "IdAttributeValue"));
    
                        // Get content translations
                        $attributes_translations = $this->__linq->fromDB("attributes_values_translations")->whereDB("IdAttributeValue IN ($ids_attributes) ORDER BY IsValid DESC, IdLanguage ASC")->getResults();
    
                        // Cycle all attributes
                        foreach($attributes as $attribute) {
    
                            // Push the format data in the responsea array
                            array_push($response, $this->formatAttribute($attribute, $attributes_translations, true));
                        }
                    }
    
                    // Return the response
                    return $this->Success($response);
                }

                return $this->Not_Found();
            }
            
            // Post
            public function create($idAttribute) {

                // Check that the attribute exists
                if ($this->checkAttribute() == true) {

                    // Create the obj to create the attribute values
                    $obj = new stdClass();
                    $obj->IdAttribute = $idAttribute;
    
                    $idAttributeValue = $this->__opHelper->object($obj)->table("attributes_values")->insert();
    
                    // Check that is not null
                    if (!Base_Functions::IsNullOrEmpty($idAttributeValue))
                        return $this->Success($idAttributeValue);
    
                    return $this->Internal_Server_Error();
                }

                return $this->Not_Found();
            }

            // Put
            public function update() {

                // Get the request
                $request = $this->Request;

                // Get the attribute
                $attribute = $this->get($request->IdAttribute, $request->IdAttributeValue);

                // Check if is success
                if ($this->Success == true) {
                    
                    // Check if the Type value is 1
                    if ($attribute->Type == Base_Attribute_Type::COLOR) {

                        // Check if the attribute value is color
                        $colors = $this->getColors($request->IdAttributeValue);

                        // Check if the colors are more than 1
                        if (count($colors) == 0)
                            return $this->Internal_Server_Error(null, "Inserisci il colore");
                    }

                    // Get the languages
                    $languages = $request->Languages;

                    // Remove Languages from request
                    unset($request->Languages);

                    // Add IsValid to 1
                    $request->IsValid = 1;
                    // Update the table
                    $this->__opHelper->object($request)->table("attributes_values")->where("IdAttributeValue")->update();

                    // Init the array for the translation values
                    $translation_values = array();

                    // Cycle all languages
                    foreach ($languages as $language) {
                        // Push the data in the translation array
                        array_push($translation_values, "($request->IdAttributeValue, '$language->Text', $language->IdLanguage, 1)");
                    }

                    // Delete the old translations
                    $sql = "DELETE FROM attributes_values_translations WHERE IdAttributeValue = $request->IdAttributeValue";
                    $this->__linq->queryDB($sql)->getResults();

                    // Insert the languages
                    $this->__opHelper->table("attributes_values_translations")->insertMassive("(IdAttributeValue, Text, IdLanguage, IsValid)", implode(", ", $translation_values));

                    return $this->Success();
                }

                return $this->Not_Found();
            }
            
            // Delete
            public function delete($idAttribute, $idAttributeValue) {

                // Check if the attribute exists
                $attribute_value = $this->get($idAttribute, $idAttributeValue);

                // Check if is success
                if ($this->Success == true) {

                    if($attribute_value->CanDelete = true) {

                        // Create the obj to delete
                        $obj = new stdClass();
                        $obj->IdAttributeValue = $idAttributeValue;
                        $obj->IsDeleted = 1;
    
                        // Update the attributes table
                        $this->__opHelper->object($obj)->table("attributes_values")->where("IdAttributeValue")->update();
    
                        return $this->Success();
                    }
                }   

                $this->Not_Found();
            }

            #region Colors

                // Get
                public function getColors($idAttributeValue) {

                    // Get all the colors of the attribute value
                    $colors = $this->__linq->fromDB("attributes_values_colors")->whereDB("IdAttributeValue = $idAttributeValue ORDER BY OrderNumber ASC")->getResults();

                    return $this->Success($colors);
                }

                // Post
                public function insertColor($idAttributeValue, $color) {

                    $max_order_number = 0;

                    // Check the default number of color 
                    if (Base_Attribute_Value_Color::DEFAULT == Base_Attribute_Value_Color::ONE_COLOR)
                        // Delete all the colors
                        $this->deleteAllColors($idAttributeValue);
                    else {

                        // Get all colors
                        $colors = $this->getColors($idAttributeValue);

                        if (count($colors) > 0)
                            $max_order_number = max(array_column($colors, "OrderNumber"));
                    } 

                    // Create the obj to insert
                    $obj = new stdClass();
                    $obj->IdAttributeValue = $idAttributeValue;
                    $obj->Color = $color;
                    $obj->OrderNumber = $max_order_number + 1;

                    $this->__opHelper->object($obj)->table("attributes_values_colors")->insert();

                    return $this->Success();
                }

                // Delete
                public function deleteColor($IdAttributeValueColor) {

                    // Create the obj to delete
                    $obj = new stdClass();
                    $obj->IdAttributeValueColor = $IdAttributeValueColor;

                    // Delete the color
                    $this->__opHelper->object($obj)->table("attributes_values_colors")->where("IdAttributeValueColor")->delete();

                    return $this->Success();
                }
                public function deleteAllColors($idAttributeValue) {

                    // Create the obj to delete
                    $obj = new stdClass();
                    $obj->IdAttributeValue = $idAttributeValue;

                    // Delete the colors
                    $this->__opHelper->object($obj)->table("attributes_values_colors")->where("IdAttributeValue")->delete();

                    return $this->Success();
                }

            #endregion

        #endregion
            
        #region Private Methods

            private function checkAttribute() {

                // Get the attribute
                $this->attribute->get($this->Request->IdAttribute);

                // Check that the attribute exists
                return $this->attribute->Success;

            }
            private function formatAttribute($attribute_value, $attributes_values_translations = null, $getAll = false) {

                // Get content translations
                if($attributes_values_translations == null)
                    $attributes_values_translations = $this->__linq->fromDB("attributes_values_translations")->whereDB("IdAttributeValue = $attribute_value->IdAttributeValue")->getResults();
            
                // Reorder
                $attributes_values_translations = $this->__linq->reorder($attributes_values_translations, "IdAttributeValue", true);

                // Build object
                $response = new stdClass();
                $response->IdAttributeValue = $attribute_value->IdAttributeValue;
                $response->Date = $attribute_value->InsertDate;
                $response->CanDelete = true;
                $response->Type = $this->__linq->fromDB("attributes")->whereDB("IdAttribute = $attribute_value->IdAttribute")->getFirstOrDefault()->Type;
                $response->LanguagesIds = array();
                
                if (!$getAll)
                    $response->Languages = array();
                
                // Check translations
                if(property_exists($attributes_values_translations, $attribute_value->IdAttributeValue)) {

                    if ($getAll == true) {

                        // Get translations
                        $response->Text = $attributes_values_translations->{$attribute_value->IdAttributeValue}[0]->Text;
                        $response->LanguagesIds = array_column($attributes_values_translations->{$attribute_value->IdAttributeValue}, "IdLanguage");
                    }   
                    else {

                        // Get translations
                        $attributes_values_translations = $attributes_values_translations->{$attribute_value->IdAttributeValue};

                        foreach ($attributes_values_translations as $translation) {

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
                $response = Base_Functions::mergeObjects($response, $this->formatEditData($attribute_value->IdCreator, $attribute_value->IdModifier, $attribute_value->InsertDate, $attribute_value->UpdateDate));

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

        #endregion

    }

?>