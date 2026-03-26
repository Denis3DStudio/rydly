<?php

    namespace Backend\Translation;

    use stdClass;
    use Base_Functions;
    use Base_Keys_Folder;
    use Base_Languages;
    use Base_Methods;
    use Base_Text_Format;
    use DOMDocument;
    use DOMXPath;
    use Translations;

    class Methods extends Base_Methods {

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }      
        #endregion

        private $id = "IdTranslation";
        private $table_name = "translations";
        private $table_translations_name = "translations_languages";

        #region Public Methods

            // Get
            public function get($idTranslation) {

                // Get translation
                $translation = $this->__linq->fromDB($this->table_name)->whereDB("$this->id = $idTranslation")->getFirstOrDefault();

                if(Base_Functions::IsNullOrEmpty($translation))
                    return $this->Not_Found();

                // Get Accounts data
                $accounts = $this->__linq->reorder($this->__linq->selectDB("DISTINCT IdAccount, Name, Surname")->fromDB("accounts")->whereDB("IdAccount IN ($translation->IdCreator, $translation->IdModifier)")->getResults(), "IdAccount");

                // Build response
                $response = new stdClass();
                $response->{$this->id} = $translation->{$this->id};
                $response->Section = trim($translation->Section ?? "");
                $response->Page = trim($translation->Page ?? "");
                $response->Label = trim($translation->Label ?? "");
                $response->Languages = array();
                $response->Note = $translation->Note;
                $response->TextFormat = property_exists($translation, "TextFormat") ? $translation->TextFormat : "";
                $response->CreatorName = $accounts->{$translation->IdCreator}->Name . " " . $accounts->{$translation->IdCreator}->Surname;;
                $response->LastModifierName = $accounts->{$translation->IdModifier}->Name . " " . $accounts->{$translation->IdModifier}->Surname;;

                // Get relative translations
                $translations = $this->__linq->fromDB($this->table_translations_name)->whereDB("$this->id = $idTranslation")->getResults();

                foreach ($translations as $translation) {

                    $language = new stdClass();
                    $language->IdTranslationLanguage = $translation->IdTranslationLanguage;
                    $language->IdLanguage = $translation->IdLanguage;
                    $language->Translation = $translation->Translation;
                    
                    array_push($response->Languages, $language);

                }
                
                return $this->Success($response);

            }
            public function getAll() {
                
                // Get translations
                $sql = "SELECT DISTINCT t.$this->id, Section, Page, Label, TextFormat, UpdateDate
                        FROM translations t
                        INNER JOIN translations_languages tl ON tl.$this->id = t.$this->id AND tl.Translation != '' AND tl.Translation IS NOT NULL
                        WHERE IsValid = 1";
                $translations = $this->__linq->queryDB($sql)->getResults();
            
                if(count($translations)) {

                    // Get translations languages
                    $sql = "SELECT DISTINCT tl.$this->id, IdLanguage, Translation
                            FROM translations_languages tl
                            INNER JOIN translations t ON t.$this->id = tl.$this->id AND t.IsValid = 1
                            WHERE tl.Translation != ''";
                    $translations_languages = $this->__linq->reorder($this->__linq->queryDB($sql)->getResults(), "$this->id", true);

                    // Set languages
                    foreach ($translations as $translation) {

                        // Get languages
                        if(!property_exists($translations_languages, $translation->{$this->id}))
                            continue;

                        $translation_languages = $translations_languages->{$translation->{$this->id}};

                        // Clean translations html
                        $translation->Translation = strip_tags($translation_languages[0]->Translation);
                        
                        // Get languages
                        $translation->Languages = array_map('intval', array_column($translation_languages, "IdLanguage"));
                        
                    }
                }

                return $this->Success($translations);

            }
            public function getSections() {

                // Get translations sections
                $this->Success(array_column($this->__linq->selectDB("DISTINCT Section")->fromDB($this->table_name)->whereDB("IsValid = 1 AND Section IS NOT NULL AND Section != ''")->getResults(), "Section"));

            }
            public function getPages() {

                // Get translations pages
                $this->Success(array_column($this->__linq->selectDB("DISTINCT Page")->fromDB($this->table_name)->whereDB("IsValid = 1 AND Page IS NOT NULL AND Page != ''")->getResults(), "Page"));

            }
            public function getLabels() {

                // Get translations labels
                $this->Success(array_column($this->__linq->selectDB("DISTINCT Label")->fromDB($this->table_name)->whereDB("IsValid = 1 AND Label IS NOT NULL AND Label != ''")->getResults(), "Label"));

            }
            public function getAllJs() {

                // Get the language id (set italian as default)
                $idLanguage = Base_Languages::ITALIAN;
                // Get the sections
                $sections = "'BACKEND'";

                // Get all the translations of BACKEND of the language
                $sql = "SELECT CONCAT(t.Page, '.', t.Label) AS Label, tl.Translation
                        FROM translations t
                        INNER JOIN translations_languages tl ON tl.$this->id = t.$this->id AND tl.IdLanguage = $idLanguage
                        WHERE t.Section IN ($sections) AND t.IsValid = 1";

                // Get the translations
                $translations = $this->__linq->queryDB($sql)->getResults();            

                // Init the response obj
                $response = new stdClass();

                // Cicle all the translations
                foreach($translations as $translation) {

                    // Set the translation
                    $response->{$translation->Label} = $translation->Translation;
                }

                // Return the response
                return $this->Success($response);
            }

            // Post
            public function create() {

                $idTranslation = $this->__opHelper->object("$this->id")->table($this->table_name)->insertIncrement();

                // Check if created
                if(is_numeric($idTranslation) && $idTranslation > 0)
                    return $this->Success($idTranslation);

                return $this->Internal_Server_Error();

            }
            public function duplicate() {

                // Get the request
                $request = $this->Request;
                
                // Check if multiple
                $isMultiple = Base_Functions::IsNullOrEmpty($request->{$this->id});

                // Get ids
                $ids = $isMultiple ? $request->IdsTranslations : [$request->{$this->id}];

                // Errors array
                $errors = [];

                // Cicle all the ids
                foreach ($ids as $id) {

                    // Get proposal data from idTranslation
                    $translation = $this->get($id);

                    // Check if not null
                    if(!Base_Functions::IsNullOrEmpty($translation)) {

                        // Insert new row
                        $newIdTranslation = $this->create();

                        // Change $this->id
                        $translation->{$this->id} = $newIdTranslation;

                        // Check if is multiple
                        if($isMultiple)
                            $translation->Page = $request->Page;
                        else                        
                            $translation->Label = $translation->Label . "_$newIdTranslation" ;

                        // Check if already exists and add to errors
                        if($this->checkDuplicate($translation) && !Base_Functions::IsNullOrEmpty($request->SaveAlways)) {

                            // Check if to save or discard
                            if($request->SaveAlways) {

                                // Set the new label
                                $translation->Label = $translation->Label . "_$newIdTranslation" ;
    
                                // Create the error
                                $obj = new stdClass();
                                $obj->{$this->id} = $newIdTranslation;
                                $obj->Spl = $translation->Section . "." . $translation->Page . "." . $translation->Label;
                                $obj->ExpectedSpl = $translation->Section . "." . $translation->Page . "." . str_replace("_$newIdTranslation", "", $translation->Label);
    
                                // Push to errors
                                array_push($errors, $obj);
                            } else 
                                $this->deletePhisically($newIdTranslation);

                        }

                        // Update request
                        $this->Request = $translation;

                        // Update new translation
                        $this->update();

                        $update = new stdClass();
                        $update->{$this->id} = $newIdTranslation;
                        $update->IsValid = $isMultiple ? 1 : 0;

                        // Update translation
                        $this->__opHelper->object($update)->table($this->table_name)->where("$this->id")->update();

                    }

                }

                return $this->Success($isMultiple ? $errors : $newIdTranslation);
            }
            public function export() {

                // Get translations
                $translations = $this->__linq->reorder($this->getTranslationsExport(null, $this->Request->IdsTranslations), "Path");

                $name = SITE_NAME;

                // Generate translations export filename
                $filename = $name . "_translations_export_" . date("YdmHi") . ".json";

                // Set path
                $path = OFF_ROOT . "/contents/translations_export/";

                // Create folder
                if(!file_exists($path))
                    mkdir($path, 0777, true);

                // Save file
                file_put_contents($path . $filename, json_encode($translations));

                // Return the filename
                return $this->Success($filename);
            }
            public function import() {
                $response = array();
                
                // Check if file has been uploaded
                if(!isset($_FILES["Files"]["name"]) || count($_FILES["Files"]["name"]) != 1)
                    return $this->Bad_Request();

                // Get file content
                $to_import = json_decode(file_get_contents($_FILES["Files"]["tmp_name"][0]));

                // Check if is a static file
                $to_import = $this->convertStaticToImport($to_import);

                // Get current translations
                $current = $this->__linq->reorder($this->getTranslationsExport(true), "Path");

                // Get keys
                $to_import_keys = array_keys((array)$to_import);
                $current_keys = array_keys((array)$current);

                // Get common and different
                $common = array_intersect($to_import_keys, $current_keys);
                $different = array();

                // Compare common and get different
                foreach ($common as $key) {
                    
                    if($to_import->{$key}->Sha != $current->{$key}->Sha) {
                        array_push($response, $to_import->{$key}->Section . " > " . $to_import->{$key}->Page . "." . $to_import->{$key}->Label);
                        array_push($different, $key);
                    }

                }

                // Get translations to import
                $to_import_keys = array_values(array_diff($to_import_keys, $current_keys));

                // Import the new
                $imported = new stdClass();
                foreach ($to_import_keys as $key) {

                    // Get translation
                    if(!property_exists($to_import, $key))
                        continue;

                    $translation = $to_import->{$key};

                    // Build path
                    $path = "$translation->Section.$translation->Page.$translation->Label";

                    // Check if already imported
                    if(!property_exists($imported, $path)) {

                        // Build object
                        $obj = new stdClass();
                        $obj->Section = $translation->Section;
                        $obj->Page = $translation->Page;
                        $obj->Label = $translation->Label;
                        $obj->Note = $translation->Note;
                        $obj->TextFormat = $translation->TextFormat;
                        $obj->IsValid = 1;

                        // Insert
                        $id = $this->__opHelper->object($obj)->table($this->table_name)->insert();

                        // Check if inserted
                        if($id === false || $id <= 0)
                            continue;

                        // Add to imported
                        $imported->{$path} = $id;

                    }
                    // Get id
                    else
                        $id = $imported->{$path};

                    // Build object
                    $obj = new stdClass();
                    $obj->{$this->id} = $id;
                    $obj->IdLanguage = $translation->IdLanguage;
                    $obj->Translation = $translation->Translation;

                    // Insert
                    $this->__opHelper->object($obj)->table($this->table_translations_name)->insert();
                    
                }

                // Check if to overwrite different and if there are different
                if($this->Request->Overwrite == 1 && count($different) > 0) {

                    foreach ($different as $key) {
                        
                        // Get translation
                        $translation = $to_import->{$key};
                        $c = $current->{$key};

                        // Update translation
                        $obj = new stdClass();
                        $obj->{$this->id} = $c->{$this->id};
                        $obj->TextFormat = $translation->TextFormat;
                        $obj->Note = $translation->Note;
                        $this->__opHelper->object($obj)->table($this->table_name)->where("$this->id")->update();

                        // Update languages
                        $obj = new stdClass();
                        $obj->Translation = $translation->Translation;
                        $id = $c->{$this->id};
                        $this->__opHelper->object($obj)->table($this->table_translations_name)->update("$this->id = $id AND IdLanguage = $translation->IdLanguage");
                        
                    }

                }

                // Regenerate cache
                $this->cache();

                return $this->Success(array_values(array_unique($response)));
            }

            // Put
            public function update() {

                $obj = $this->Request;

                // Check if translation exists
                $translation = $this->get($obj->{$this->id});

                if(!$this->Success)
                    return;

                // Slug
                $translation->Section = strtoupper(str_replace("-", "_", Base_Functions::Slug($obj->Section)));
                $translation->Page = strtoupper(str_replace("-", "_", Base_Functions::Slug($obj->Page)));
                $translation->Label = strtoupper(str_replace("-", "_", Base_Functions::Slug($obj->Label)));
                $translation->Note = $obj->Note;

                // Check if already exists
                if($this->checkDuplicate($translation))
                    return $this->Bad_Request("Translation already exists");

                // Set valid
                $translation->IsValid = 1;
                $translation->TextFormat = $obj->TextFormat;

                // Update
                $this->__opHelper->object($translation)->table($this->table_name)->where("$this->id")->update();

                // Reorder
                $translation->Languages = $this->__linq->reorder($translation->Languages, "IdLanguage");

                // Update languages
                foreach ($obj->Languages as $language) {
                    $update = new stdClass();

                    // Get id language
                    $update->IdLanguage = $language->IdLanguage;

                    // Check if the translation has not html
                    if ($obj->TextFormat != Base_Text_Format::HTML) {

                        // remove from text the tags <p></p> and create an array with the text
                        $array_of_strings = Base_Functions::getTextInTags($language->Translation, "<p>", "</p>");

                        // Check if array_of_strings is null or not
                        if(Base_Functions::IsNullOrEmpty($array_of_strings)) 
                            $array_of_strings = array($language->Translation);

                        // From array to string 
                        $language->Translation = strip_tags(implode("<br><br>", $array_of_strings), "<br>");
                    }

                    // Set properties
                    if (!Base_Functions::IsNullOrEmpty($language->Translation))
                        $update->Translation = (Base_Functions::IsNullOrEmpty(strip_tags($language->Translation))) ? null : $language->Translation;
                    
                    // Check if exists
                    if(property_exists($translation->Languages, $language->IdLanguage) && !Base_Functions::IsNullOrEmpty($language->Translation))
                        $this->__opHelper->object($update)->table($this->table_translations_name)->update("IdTranslationLanguage = " . $translation->Languages->{$language->IdLanguage}->IdTranslationLanguage);

                    // Insert
                    elseif(!Base_Functions::IsNullOrEmpty($language->Translation)) {
                        $update->{$this->id} = $translation->{$this->id};

                        $this->__opHelper->object($update)->table($this->table_translations_name)->insert();
                    }

                    // Try to Delete
                    else 
                        $this->__linq->queryDB("DELETE FROM translations_languages WHERE $this->id = " . $translation->{$this->id} . " AND IdLanguage = $language->IdLanguage")->getResults();

                }

                // Delete cache and recreate
                $this->cache();

                return $this->Success();

            }

            // Delete
            public function delete($idTranslation) {

                // Check if translation exists
                $this->get($idTranslation);

                if(!$this->Success)
                    return;

                // Delete
                $del = new stdClass();
                $del->{$this->id} = $idTranslation;
                $this->__opHelper->object($del)->table($this->table_name)->where("$this->id")->delete();
                $this->__opHelper->object($del)->table($this->table_translations_name)->where("$this->id")->delete();

                // Delete cache and recreate
                $this->cache();

                return $this->Success();

            }
            
            #region Deepl

                // Get
                public function getTranslationDeepl() {

                    // Get the request
                    $request = $this->Request;
    
                    // Get the languages into which we need to translate
                    $targetLanguages = !in_array('-1', $request->LanguagesTo) ? array_map('intval', $request->LanguagesTo) : Base_Languages::ALL;
    
                    // Cicle all the languages to get the translations
                    $toTranslate = $request->ToTranslate;
    
                    // Build the response
                    $response = new stdClass();
                    
                    // Cicle all languages
                    foreach($targetLanguages as $language) {
    
                        // Check if the language is the source language
                        if($language == $request->LanguageFrom)
                            continue;
    
                        // Build the language obj
                        $response->{$language} = new stdClass();
                        $response->{$language}->Language = $language;
                        $response->{$language}->Translations = new stdClass();
    
                        // Cicle all the toTranslate fields
                        foreach ($toTranslate as $field) {
    
                            // Check if the translation has not html
                            if ($field->TextFormat != Base_Text_Format::HTML && !Base_Functions::IsNullOrEmpty($field->Text)) {
    
                                // remove from text the tags <p></p> and create an array with the text
                                $array_of_strings = Base_Functions::getTextInTags($field->Text, "<p>", "</p>");
    
                                // Check if array_of_strings is null or not
                                if(Base_Functions::IsNullOrEmpty($array_of_strings)) 
                                    $array_of_strings = array($field->Text);
    
                                // From array to string 
                                $field->Text = strip_tags(implode("<br><br>", $array_of_strings), "<br>");
                            }
    
                            // Build the obj for the translation
                            $obj = new stdClass();
                            $obj->TextToTranslate = $field->Text;
                            $obj->LanguageTarget = $language;
                            $obj->SourceLanguage = $request->LanguageFrom;
                            $obj->TextFormat = $field->TextFormat;
                            
                            // Get the translation
                            $response->{$language}->Translations->{$field->Name} = $field->TextFormat == Base_Text_Format::URL ? $this->translateUrl($field->Text, $request->LanguageFrom, $language) : $this->getDeeplTranslation($obj);
                        }
                    }
    
                    // Return all the translations
                    return $this->Success($this->__linq->back_reorder($response));
                    
                }
                public function getCostDeepl() {
    
                    // Get language 
                    $text = $this->Request->Text;
    
                    // Check if the translation has not html
                    if ($this->Request->TextFormat != Base_Text_Format::HTML) {
    
                        // remove from text the tags <p></p> and create an array with the text
                        $array_of_strings = Base_Functions::getTextInTags($text, "<p>", "</p>");
    
                        // Check if array_of_strings is null or not
                        if(Base_Functions::IsNullOrEmpty($array_of_strings)) 
                            $array_of_strings = array($text);
    
                        // From array to string 
                        $text = strip_tags(implode("<br><br>", $array_of_strings), "<br>");
                    }
    
                    
                    // Calculate the cost
                    $cost = Base_Functions::formatAsCurrency((strlen($text) * $this->Request->FinalLanguages), ',', '.', 0);
    
                    // Get Response
                    $response = $this->getDeeplLimit();
    
                    // Check if the response is not null
                    if(Base_Functions::IsNullOrEmpty($response))
                        return $this->Not_Found();
    
                    // Add the Character Remaining
                    $response->character_remaining = Base_Functions::formatAsCurrency(intval(str_replace('.', '', $response->character_remaining)) - $cost, ',', '.', 0);;
    
                    $response->character_cost = $cost;
                    // Return the length 
                    return $this->Success($response);
                }

                // Post
                public function getDeeplTranslation($obj) {

                    // Set the authKey of DeepL account
                    $authKey = DEEPL_AUTH_KEY;
    
                    // Build the request
                    $data = array(
                        'text' => array($obj->TextToTranslate ?? ""),
                        "source_lang" => Base_Languages::DEEPL_ABBREVIATIONS_SOURCE[intval($obj->SourceLanguage)],
                        'target_lang' => Base_Languages::DEEPL_ABBREVIATIONS[intval($obj->LanguageTarget)],
                        'preserve_formatting' => true
                    );
    
                    if($obj->TextFormat == Base_Text_Format::HTML)
                        $data['tag_handling'] = 'html';
    
                    // Make the post into json
                    $postData = json_encode($data);
    
                    // Api URL
                    $url = 'https://api.deepl.com/v2/translate';
    
                    // Init the curl
                    $ch = curl_init();
    
                    // Set request for the api
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Authorization: DeepL-Auth-Key ' . $authKey
                    ));
    
                    // Make the curl execute
                    $response = curl_exec($ch);
    
                    // Check errors
                    if(curl_errno($ch)) {
                        echo 'Errore cURL: ' . curl_error($ch);
                    }
    
                    // Close the curl
                    curl_close($ch);
    
                    // Decode the respose
                    $result = json_decode($response, true);
    
                    return $result['translations'][0]['text'];
                }
                public function translateAll() {

                    // Get the request
                    $request = $this->Request;

                    // Get the languages into which we need to translate
                    $targetLanguages = array_diff(!in_array('-1', $request->LanguagesTo) ? array_map('intval', $request->LanguagesTo) : Base_Languages::ALL, array($request->LanguageFrom));

                    // Get all the translations to translate
                    $tranlsations = $this->getAll();

                    // Check if the translations are not null
                    if(Base_Functions::IsNullOrEmpty($tranlsations))
                        return $this->Success();

                    // Get all the values of the translations
                    $tranlations_values = $this->__linq->reorder($this->__linq->fromDB($this->table_translations_name)->whereDB("$this->id IN (" . implode(",", array_column($tranlsations, "$this->id")) . ")")->getResults(), "$this->id", true);

                    // Cicle all the translations
                    foreach($tranlsations as $translation) {

                        // Get the values of the translation
                        $values = $this->__linq->reorder($tranlations_values->{$translation->{$this->id}}, "IdLanguage");

                        // Check if the values are not null
                        if(Base_Functions::IsNullOrEmpty($values) || Base_Functions::IsNullOrEmpty($values->{$request->LanguageFrom}))
                            continue;

                        // Translate
                        foreach($targetLanguages as $language) {

                            // Build the obj for the translation
                            $obj = new stdClass();
                            $obj->TextToTranslate = $values->{$request->LanguageFrom}->Translation;
                            $obj->LanguageTarget = $language;
                            $obj->SourceLanguage = $request->LanguageFrom;
                            $obj->TextFormat = $translation->TextFormat;

                            // Get the translation
                            $newTranlation = $translation->TextFormat == Base_Text_Format::URL ? $this->translateUrl($values->{$request->LanguageFrom}, $request->LanguageFrom, $language) : $this->getDeeplTranslation($obj);

                            // Create the obj for the translation
                            $obj = new stdClass();
                            $obj->Translation = $newTranlation;

                            // Update or Insert the translation
                            if(in_array($language, $translation->Languages)) {

                                // Add the IdTranslationLanguage
                                $obj->IdTranslationLanguage = $values->{$language}->IdTranslationLanguage;

                                // Update the translation
                                $this->__opHelper->object($obj)->table($this->table_translations_name)->where("IdTranslationLanguage")->update();

                            } else {

                                // Add the IdLanguage
                                $obj->IdLanguage = $language;
                                $obj->{$this->id} = $translation->{$this->id};

                                // Insert the translation
                                $this->__opHelper->object($obj)->table($this->table_translations_name)->insert();
                                
                            }

                        }
                    }

                    // Delete cache and recreate
                    $this->cache();

                    // Return the success
                    return $this->Success();
                }
                
            #endregion

            #region Cache

                // Post
                public function cache() {

                    // Check last update date
                    $last_update = $this->__linq->selectDB("MAX(UpdateDate) as UpdateDate")->fromDB($this->table_name)->getFirstOrDefault();

                    if(Base_Functions::IsNullOrEmpty($last_update))
                        return $this->Success();

                    // Get last date
                    $last_update = Base_Functions::FormatDate("YmdHis", $last_update->UpdateDate);

                    // Generate new file
                    Translations::GenerateCache($last_update);

                    return $this->Success();

                }

                // Delete
                public function deleteCache() {

                    Translations::DeleteCache();

                    return $this->Success();

                }

            #endregion

            #region File

                // Get
                public function getFileAll() {

                    // Get the request
                    $request = $this->Request;

                    // Get the macro folder
                    $path = "/" . Base_Keys_Folder::NAMES[$request->Folder ?? Base_Keys_Folder::BACKEND] . "/pages/";

                    // Get all the file from the the folder /frontend/pages/
                    $folders = scandir($_SERVER["DOCUMENT_ROOT"] . $path);
    
                    // Get all the files from the folder
                    $files = array();
    
                    // Cicle all the folders
                    foreach($folders as $folder) {
    
                        // Check if the folder is not a folder
                        if(is_dir($_SERVER["DOCUMENT_ROOT"] . $path . $folder) && $folder !== "." && $folder !== "..") {
    
                            // Get all the files from the folder
                            $files_tim = scandir($_SERVER["DOCUMENT_ROOT"] . $path . $folder);
    
                            // Cicle all the files
                            foreach($files_tim as $file) {
    
                                // Check if the file is not a folder
                                if(!is_dir($_SERVER["DOCUMENT_ROOT"] . $path . $folder . "/" . $file) && $file !== "." && $file !== "..") {
    
                                    // Build the obj
                                    $obj = new stdClass();
                                    $obj->Folder = ucfirst($folder);
                                    $obj->FileName = $file;
                                    $obj->Path = $path . $folder . "/" . $file;
    
                                    // Add the file to the array
                                    array_push($files, $obj);
                                }
                            }
                        }
                    }
    
                    // Reorder the files
                    $files = $this->__linq->reorder($files, "Folder", true);
    
                    // Return the files
                    return $this->Success($files);
                }
                public function translateFile() {

                    // Get the request
                    $request = $this->Request;

                    // Cicle all the files
                    foreach($request->Files as $file) {

                        // Get the new file
                        $tmp = $this->replaceWithTranslations($_SERVER["DOCUMENT_ROOT"] . $file->Path, $file->Section, $file->Page);

                        // Check if all the translations are saved
                        $errors = $this->createTranslations($tmp->ToTranslate, $request->Languages->LanguageFrom, $request->Languages->LanguagesTo);

                        // Replace all the errors
                        foreach($errors as $error) {
                            $tmp->Html = str_replace($error->OldName, $error->NewName, $tmp->Html);
                        }

                        // Save the file into the same path but with the _translated suffix
                        file_put_contents($_SERVER["DOCUMENT_ROOT"] . str_replace(".php", "_translated.php", $file->Path), $tmp->Html);
                    }

                    // Return the success
                    return $this->Success();
                }
                public function replaceWithTranslations($filePath, $section, $page) {

                    // Check if the file exists
                    if (!file_exists($filePath))
                        return $this->Not_Found(null, "File not found");

                    // Clear the html
                    $file = $this->clearHtml($filePath, $section, $page);
                
                    // Build the new file
                    $newFile = $this->buildNewFile($file->Html, $file->Section, $file->Page);

                    // Re-add the css and the scripts
                    $newFile = $this->setCssAndScripts($newFile, $file->Css, $file->Scripts);

                    // Return 
                    $response = new stdClass();
                    $response->ToTranslate = $newFile->ToTranslate;
                    $response->Html = $newFile->Html;
                    $response->Path = $filePath;

                    // Return the translation
                    return $response;
                }

            #endregion

        #endregion

        #region Private Methods

            private function getTranslationsExport($get_id = false, $idsTranslations = null) {

                // Check if get id
                $select = $get_id ? "t.$this->id, " : "";

                // Explode ids
                $ids = Base_Functions::IsNullOrEmpty($idsTranslations) ? "" : "AND t.$this->id IN ($idsTranslations)";

                // Get all valid translations and languages
                $sql = "SELECT $select CONCAT(t.Section, '.', t.Page, '.', t.Label, '_', tl.IdLanguage) AS Path, t.Section, t.Page, t.Label, t.Note, t.TextFormat, tl.IdLanguage, tl.Translation, SHA1(tl.Translation) AS Sha
                        FROM translations t
                        INNER JOIN translations_languages tl ON tl.$this->id = t.$this->id AND tl.Translation != '' AND tl.Translation IS NOT NULL $ids
                        WHERE t.IsValid = 1";
                return $this->__linq->queryDB($sql)->getResults();

            }
            private function convertStaticToImport($values) {
                $response = new stdClass();
                $isStatic = false;

                // Get keys
                $keys = array_keys((array)$values);

                // Get languages abbreviations
                $languages = array_map('strtoupper', array_values(Base_Languages::ABBREVIATIONS));

                // Check if has the languages keys
                foreach ($languages as $language) {
                    if(in_array($language, $keys)) {
                        $isStatic = true;
                        break;
                    }
                }

                // Check if not is static
                if(!$isStatic)
                    return $values;

                // Format
                foreach ($values as $language => $sections) {
                    
                    // Get language id
                    $idLanguage = Base_Languages::IDS[strtolower($language)];

                    // Cicle all the sections
                    foreach ($sections as $section => $pages) {
                        
                        // Cicle all the pages
                        foreach ($pages as $page => $labels) {
                            
                            // Cicle all the labels
                            foreach ($labels as $label => $translation) {

                                // Build path
                                $path = "$section.$page.$label";
                                
                                // Build obj
                                $obj = new stdClass();
                                $obj->Path = $path;
                                $obj->Section = $section;
                                $obj->Page = $page;
                                $obj->Label = $label;
                                $obj->Note = null;
                                $obj->TextFormat = strip_tags($translation) == $translation ? Base_Text_Format::NORMAL : Base_Text_Format::HTML;
                                $obj->IdLanguage = $idLanguage;
                                $obj->Translation = $translation;
                                $obj->Sha = sha1($translation);

                                // Add to response
                                $response->{$path} = $obj;

                            }
                            

                        }

                    }

                }

                return $response;

                /*

                "WEBSITE.HOME.TITLE_1": {
                    "Path": "WEBSITE.HOME.TITLE_1",
                    "Section": "WEBSITE",
                    "Page": "HOME",
                    "Label": "TITLE",
                    "Note": null,
                    "TextFormat": 0,
                    "IdLanguage": 1,
                    "Translation": "Benvenuto del PHP Base",
                    "Sha": "6128ba13c2d555ba67f8b5bc7faf66efc6e46ca6"
                },
                 
                 */
            }
            private function deletePhisically($idTranslation) {

                // Create the obj
                $obj = new stdClass();
                $obj->{$this->id} = $idTranslation;

                // Delete the new translation
                $this->__opHelper->object($obj)->table($this->table_name)->where("$this->id")->delete();
                $this->__opHelper->object($obj)->table($this->table_translations_name)->where("$this->id")->delete();

            }
            private function checkDuplicate($translation) {

                // Build the where
                $idWhere = property_exists($translation, $this->id) ? "$this->id != " . $translation->{$this->id} . " AND " : "";

                if(!Base_Functions::IsNullOrEmpty($this->__linq->fromDB($this->table_name)->whereDB("$idWhere Section = '$translation->Section' AND Page = '$translation->Page' AND Label = '$translation->Label' AND IsValid = 1")->getFirstOrDefault()))
                    return true;

                return false;
            }

            #region Deepl

                private function translateUrl($translation, $languageFrom, $language) {

                    // Build the response
                    $response = array();

                    if (is_object($translation))
                        $translation = $translation->Translation;

                    // Slit the translation
                    $array_of_strings = explode("/", $translation);

                    // If the array_of_strings is empty try to split by " "
                    if(count($array_of_strings) == 1)
                        $array_of_strings = explode(" ", $translation);

                    // Unset all the empty strings
                    $array_of_strings = array_values(array_filter($array_of_strings));      
                    
                    // Unset the first element if is the language from
                    if($array_of_strings[0] == Base_Languages::ABBREVIATIONS[$languageFrom])
                        unset($array_of_strings[0]);

                    // Cicle all the strings
                    foreach ($array_of_strings as $string) {
                        
                        // Build the obj for the translation
                        $obj = new stdClass();
                        $obj->TextToTranslate = str_replace("-", " ", $string);
                        $obj->LanguageTarget = $language;
                        $obj->SourceLanguage = $languageFrom;
                        $obj->TextFormat = Base_Text_Format::URL;

                        // Get the translation
                        array_push($response, Base_Functions::Slug($this->getDeeplTranslation($obj)));

                    }

                    // Build the url
                    return str_replace("//", "/", Base_Languages::URL_ABBREVIATIONS[$language] . implode("/", $response)) . "/";
                }

            #endregion

            #region Html

                private function buildNewFile($html, $section, $page) {

                    // Get all the php blocks
                    $phpBlocks = [];

                    // Replace PHP blocks with placeholders
                    $html = preg_replace_callback('/<\?(?:php)?(.*?)\?>/s', function ($matches) use (&$phpBlocks) {

                        // Generate a unique key for this PHP block
                        $key = '<tmp>{{PHP_BLOCK_' . count($phpBlocks) . '}}</tmp>';

                        // Save the PHP block
                        $phpBlocks[$key] = $matches[0]; 

                        // Replace the PHP block with the key
                        return $key;
                    }, $html);
                
                    // Init the DOMDocument
                    $dom = new DOMDocument();
                    libxml_use_internal_errors(true);
                    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                    libxml_clear_errors();
                
                    // Get xpath
                    $xpath = new DOMXPath($dom);
                    // Get all the text nodes that are not empty
                    $textNodes = $xpath->query('//text()[normalize-space(.) != ""]');
                
                    // Init the translation index and the translations placeholder
                    $translationIndex = 1;
                    $toTranslate = [];

                    // Cicle all the text nodes
                    foreach ($textNodes as $node) {
                        $parent = $node->parentNode;

                        // Get the original text
                        $originalText = trim($node->nodeValue);
                
                        // Check if the text contains a placeholder
                        if (strpos($originalText, '{{PHP_BLOCK_') !== false || strpos($originalText, '{{CSS_}}') !== false || strpos($originalText, '{{SCRIPT_') !== false)
                            continue;
                
                        // If the text is not a PHP block and is not empty
                        if (!preg_match('/<\?php/', $originalText) && !preg_match('/^\s*$/', $originalText)) {

                            // Build the new text
                            $newText = '<?=__t("' . strtoupper($page) .  '.TRANSLATION_' . $translationIndex . '")?>';

                            // Build the translation
                            $obj = new stdClass();
                            $obj->Section = strtoupper($section);
                            $obj->Page = strtoupper($page);
                            $obj->Label = 'TRANSLATION_' . $translationIndex;
                            $obj->Text = $originalText;
                            
                            // Push to the translations
                            array_push($toTranslate, $obj);

                            // Set the new text
                            $newNode = $dom->createTextNode($newText);

                            // Replace the text node with the new text
                            $parent->replaceChild($newNode, $node);

                            // Increment the translation index
                            $translationIndex++;
                        }
                    }
                
                    // Replace the placeholders with the PHP blocks
                    $translatedHtml = html_entity_decode($dom->saveHTML(), ENT_NOQUOTES, 'UTF-8');

                    // Cicle to replace the PHP blocks
                    foreach ($phpBlocks as $key => $phpCode) {

                        // Replace the PHP block with the original PHP code
                        $translatedHtml = str_replace($key, $phpCode, $translatedHtml);
                    }

                    // Build the response
                    $response = new stdClass();
                    $response->Html = $translatedHtml;
                    $response->ToTranslate = $toTranslate;

                    return $response;
                }
                private function clearHtml($filePath, $section, $page = null) {

                    // Get the html content
                    $html = file_get_contents($filePath);

                    // Build the response
                    $response = new stdClass();

                    // Get the css
                    $tmp = $this->getCss($html);

                    // Get new html
                    $html = $tmp->Html;
                    $response->Css = $tmp->Css;

                    // Get the scripts
                    $tmp = $this->getScripts($html);
                    
                    // Get new html
                    $html = $tmp->Html;
                    $response->Scripts = $tmp->Scripts;
                
                    // Remove the css
                    $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

                    // Remove the scripts
                    $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);

                    $response->Html = $html;

                    // Add the Section of the file
                    $response->Section = $section;

                    // Add the Page of the file
                    $response->Page = $page ?? pathinfo($filePath, PATHINFO_FILENAME);

                    // Return the response
                    return $response;
                } 
                private function getCss($html) {

                    // Get the css
                    preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $html, $matches);

                    $css = [];

                    // Cicle all the matches
                    foreach ($matches[0] as $match) {
                        // Replace the css page with {{CSS_}}
                        $html = str_replace($match, '{{CSS_}}', $html);

                        // Add the css to the array
                        $css['{{CSS_}}'] = $match;
                    }

                    // Build the response
                    $response = new stdClass();
                    $response->Html = $html;
                    $response->Css = $css;

                    return $response;
                }
                private function getScripts($html) {

                    // Get the scripts
                    preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $html, $matches);

                    $scripts = [];

                    // Cicle all the matches
                    foreach ($matches[0] as $key => $match) {
                        // Replace the script page with {{SCRIPT_NUMBER}}
                        $html = str_replace($match, '{{SCRIPT_' . $key . '}}', $html);

                        // Add the script to the array
                        $scripts['{{SCRIPT_' . $key . '}}'] = $match;
                    }

                    // Build the response
                    $response = new stdClass();
                    $response->Html = $html;
                    $response->Scripts = $scripts;

                    return $response;
                }
                private function setCssAndScripts($file, $css, $scripts) {

                    // Replace the css
                    foreach ($css as $key => $singleCss) {
                        $file->Html = str_replace($key, $singleCss, $file->Html);
                    }

                    // Replace the scripts
                    foreach ($scripts as $key => $script) {
                        $file->Html = str_replace($key, $script, $file->Html);
                    }

                    return $file;
                }
                private function createTranslations($tranlsations, $languageFrom, $languagesTo) {

                    // Init the errors
                    $errors = array();

                    // Cicle all the translations
                    foreach ($tranlsations as $translation) {

                        $obj = new stdClass();
                        $obj->Section = strtoupper(str_replace("-", "_", Base_Functions::Slug($translation->Section)));
                        $obj->Page = strtoupper(str_replace("-", "_", Base_Functions::Slug($translation->Page)));
                        $obj->Label = strtoupper(str_replace("-", "_", Base_Functions::Slug($translation->Label)));
                        $obj->Note = null;
                        $obj->TextFormat = Base_Text_Format::HTML;

                        // Check for duplicates
                        if($this->checkDuplicate($translation)) {
                            
                            // Build the new label
                            $lastId = $this->__linq->selectDB("MAX($this->id) as Id")->fromDB($this->table_name)->getFirstOrDefault()->Id;

                            // Build the new label
                            $obj->Label = strtoupper(str_replace("-", "_", Base_Functions::Slug($translation->Label))) . "_" . ($lastId + 1);

                            // Build the new 
                            $error = new stdClass();
                            $error->OldName = $obj->Page . "." . $translation->Label;
                            $error->NewName = $obj->Page . "." . $obj->Label;

                            // Add to errors
                            array_push($errors, $error);
                        }

                        // Insert the translation
                        $translation->{$this->id} = $this->__opHelper->object($obj)->table($this->table_name)->insert();

                        // Insert the language
                        $obj = new stdClass();
                        $obj->{$this->id} = $translation->{$this->id};
                        $obj->IdLanguage = $languageFrom;
                        $obj->Translation = $translation->Text;

                        // Insert the translation
                        $this->__opHelper->object($obj)->table($this->table_translations_name)->insert();

                        // Delete the language from the languagesTo
                        $languagesTo = array_diff(!in_array('-1', $languagesTo) ? $languagesTo : Base_Languages::ALL, array($languageFrom));

                        // Build the massive
                        $massive = array();

                        // Cicle all the other languages
                        foreach ($languagesTo as $language) {

                            // Build the obj
                            $obj = new stdClass();
                            $obj->{$this->id} = $translation->{$this->id};
                            $obj->TextToTranslate = $translation->Text;
                            $obj->SourceLanguage = $languageFrom;
                            $obj->LanguageTarget = $language;
                            $obj->TextFormat = Base_Text_Format::HTML;

                            // Get the translation
                            $newTranlation = $this->getDeeplTranslation($obj);

                            // Create the obj for the translation
                            $obj = new stdClass();
                            $obj->{$this->id} = $translation->{$this->id};
                            $obj->IdLanguage = $language;
                            $obj->Translation = $newTranlation;

                            // Format for the massive
                            $obj = Base_Functions::convertForMassive($obj);

                            // Add to the massive
                            array_push($massive, "(" . $translation->{$this->id} . " , $language, $obj->Translation)");
                        }

                        // Insert the massive
                        $this->__opHelper->table($this->table_translations_name)->insertMassive("(IdTranslation, IdLanguage, Translation)", implode(", ", $massive));

                        // If success update the translation
                        if($this->Success) {

                            // Build the obj
                            $obj = new stdClass();
                            $obj->{$this->id} = $translation->{$this->id};
                            $obj->IsValid = 1;

                            // Update the translation
                            $this->__opHelper->object($obj)->table($this->table_name)->where("$this->id")->update();
                        }
                    }

                    return $errors;
                }

            #endregion

        #endregion

    }

