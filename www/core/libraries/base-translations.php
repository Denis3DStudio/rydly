<?php

class Translations {

    #region Properties

        public static $DefaultSection = "WEBSITE";
        public static $IdLanguage = Base_Languages::ITALIAN;
        public static $ShowLabel = false;
        public static $ShowMarked = false;

        private static $Translations = null;
        private static $TranslationsPath = OFF_ROOT . "/contents/";

    #endregion

    #region Public Methods

        /**
         * Generate the translations' file cache
         */
        public static function GenerateCache($last_update = null) {

            // Check if null
            if($last_update == null) $last_update = date("YmdHis");

            // Check if defined the database
            if(!defined("DATABASE_NAME")) return;

            // Init
            $linq = new Base_LINQHelper();

            // Check if table exists
            $sql = "SELECT * FROM information_schema.tables WHERE table_schema = '" . DATABASE_NAME . "' AND table_name = 'translations_languages' LIMIT 1";
            $table = $linq->queryDB($sql)->getResults();

            // Check
            if(Base_Functions::IsNullOrEmpty($table)) {
                
                // Create folder
                $folder = self::$TranslationsPath . "translations/";

                if(!file_exists($folder))
                    mkdir($folder, 0777, true);

                return;

            }

            // Get languages
            $languages = Base_Languages::ALL;

            // Create folder path
            $folder_path = self::$TranslationsPath . "new_translations";

            if(!file_exists($folder_path))
                mkdir($folder_path, 0777, true);

            foreach ($languages as $language) {
                
                // Get all translations by this language
                $translations_languages = $linq->selectDB("IdTranslation, Translation")->fromDB("translations_languages")->whereDB("IdLanguage = $language")->getResults();

                if(Base_Functions::IsNullOrEmpty($translations_languages))
                    continue;

                // Create folder
                $language_folder = $folder_path . "/" . Base_Languages::ABBREVIATIONS[$language];
                mkdir($language_folder, 0777);

                // Get ids
                $ids = implode(", ", array_column($translations_languages, "IdTranslation"));

                // Get translations
                $translations = $linq->selectDB("IdTranslation, Section, Page, Label")->fromDB("translations")->whereDB("IdTranslation IN ($ids) AND IsValid = 1")->getResults();

                // Reorder language
                $translations_languages = $linq->reorder($translations_languages, "IdTranslation");

                // Reorder translations
                $translations_sections = $linq->reorder($translations, "Section", true);

                foreach ($translations_sections as $section => $translations_section) {
                    
                    // Build folder section path
                    $section_folder = $language_folder . "/" . strtoupper($section);

                    // Check if folder exists then create
                    if(!file_exists($section_folder))
                        mkdir($section_folder, 0777);

                    // Reorder by page
                    $translations_pages = $linq->reorder($translations_section, "Page", true);

                    foreach ($translations_pages as $page => $translations_page) {

                        // Build page file
                        $page_file = $section_folder . "/" . strtoupper($page) . ".json";

                        // Build object
                        $file_content = new stdClass();

                        foreach ($translations_page as $translation) {    

                            // Get translation
                            $tr = $translations_languages->{$translation->IdTranslation};

                            $file_content->{$translation->Label} = $tr->Translation;

                        }
                                
                        // Insert in file
                        file_put_contents($page_file, json_encode($file_content));

                    }

                }

            }

            // Delete old cache
            Base_Functions::deleteFiles(self::$TranslationsPath . "translations/");

            // Rename
            rename($folder_path, self::$TranslationsPath . "translations");

        }

        /**
         * Get translation
         */
        public static function Translation($translation, $only_check = false) {

            // Load translations
            self::LoadTranslations();

            // Check translations
            if(Base_Functions::IsNullOrEmpty(self::$Translations))
                return IS_DEBUG && !$only_check ? "<mark><del>$translation</del></mark>" : null;
        
            // Set original
            $original = $translation;

            // Explode translation
            $translation = explode(".", $translation);

            // Check validity
            if(count($translation) < 2 || count($translation) > 3) {
                if(!IS_DEBUG)
                    throw new Exception("Asked translation not valid", 1);
                else
                    return null;
            }

            // Get section, page and label
            $section = count($translation) == 3 ? strtoupper(str_replace("-", "_", Base_Functions::Slug(array_shift($translation)))) : self::$DefaultSection;
            $page = strtoupper(str_replace("-", "_", Base_Functions::Slug($translation[0])));
            $label = strtoupper(str_replace("-", "_", Base_Functions::Slug($translation[1])));

            // Check if exists
            if(property_exists(self::$Translations, $section) && property_exists(self::$Translations->{$section}, $page) && property_exists(self::$Translations->{$section}->{$page}, $label)) {

                // Get translation
                $translation = self::$Translations->{$section}->{$page}->{$label};

                // Check if to show the label
                if(self::$ShowLabel)
                    return $original;

                // Check if to show the marked translation
                if(self::$ShowMarked)
                    return "<span style='background-color: red;'>$translation</span>";

                // Return translation
                return html_entity_decode($translation);            

            }
            else
                return IS_DEBUG && !$only_check ? "<mark><del>" . implode(".", [$section, $page, $label]) . "</del></mark>" : null;

        }

        /**
         * Return the translations as a JSON object
         */
        public static function TranslationsJSON() {
            $response = new stdClass();

            // Load translations
            self::LoadTranslations();

            // Check translations
            if(Base_Functions::IsNullOrEmpty(self::$Translations))
                return json_encode($response);

            // Parse translations
            foreach (self::$Translations as $section => $pages) {

                // Check if the section is not EMAIL 
                if($section == "EMAIL")
                    continue;
                
                $response->{$section} = new stdClass();
                
                foreach ($pages as $page => $labels) {
                    $response->{$section}->{$page} = new stdClass();

                    foreach ($labels as $label => $translation)
                    $response->{$section}->{$page}->{$label} = html_entity_decode($translation);

                }
            }

            // Return translations
            return $response;

        }

        /**
         * Delete the translations cache
         */
        public static function DeleteCache() {
            Base_Functions::deleteFiles(self::$TranslationsPath . "translations/");
        }

    #endregion

    #region Private Methods

        /**
         * Get current folder
         */
        private static function CurrentFolder($second = false) {

            $folder = self::$TranslationsPath . "translations/";
            
            if(!file_exists($folder)) {

                // Check if already tried
                if($second) return null;
                
                // Generate cache
                self::GenerateCache();

                // Retry
                return self::CurrentFolder(true);

            }

            // Check if there is the static file
            if(defined("STATIC_TRANSLATION_FILE") && !Base_Functions::IsNullOrEmpty(STATIC_TRANSLATION_FILE) && file_exists($_SERVER["DOCUMENT_ROOT"] . STATIC_TRANSLATION_FILE)) 
                return $_SERVER["DOCUMENT_ROOT"] . STATIC_TRANSLATION_FILE;

            // Get language abbreviation
            $language = Base_Languages::ABBREVIATIONS[self::$IdLanguage];

            // Check language folder if exists
            if(!file_exists("$folder$language"))
                return null;

            return "$folder$language";
            
        }

        /**
         * Load translations in the object
         */
        private static function LoadTranslations() {

            // Check if already loaded
            if(!Base_Functions::IsNullOrEmpty(self::$Translations)) return;

            // Init translations
            self::$Translations = new stdClass();

            // Get current folder
            $folder = self::CurrentFolder();

            // Folder not found
            if(Base_Functions::IsNullOrEmpty($folder)) return;

            // Check if is a file
            if(is_file($folder)) {

                // Get content
                $content = json_decode(file_get_contents($folder));

                // Get language abbreviation
                $language = strtoupper(Base_Languages::ABBREVIATIONS[self::$IdLanguage]);

                // Check language
                if(!property_exists($content, $language)) return;

                // Set translations
                self::$Translations = $content->{$language};

                return;

            }

            // Get all sections
            $sections = glob("$folder/*");

            // Loop sections
            foreach ($sections as $section_path) {

                // Init
                $section = new stdClass();

                // Get all pages
                $pages = glob("$section_path/*");

                foreach ($pages as $page_path) {
                    
                    // Get page name
                    $page = strtoupper(basename($page_path, ".json"));

                    // Get page content
                    $page_content = json_decode(file_get_contents($page_path));

                    // Set page
                    $section->{$page} = $page_content;
                    
                }

                // Set section
                self::$Translations->{strtoupper(basename($section_path))} = $section;

            }

        }

    #endregion

}

/**
 * Return the translation in the current language
 * @param string the relation string of the translation
 * @return string
 */
function __t($translation) {

    // Check if null
    if(Base_Functions::IsNullOrEmpty($translation)) {
        if(IS_DEBUG)
            throw new Exception("Asked translation not valid", 1);
        else
            return null;
    }

    // Get args
    $args = func_get_args();

    // Remove first arg
    array_shift($args);

    $only_check = count($args) == 1 && is_bool($args[0]) ? $args[0] : false;
    
    // Get translation
    $translation = Translations::Translation($translation, $only_check);

    // Check if args
    if($only_check == false && !Base_Functions::IsNullOrEmpty($translation)) {

        // Set needle
        $needle = "(*)";

        // Replace args
        foreach ($args as $value) {
            $pos = strpos($translation, $needle);

            // Check if found
            if ($pos !== false && !Base_Functions::IsNullOrEmpty($value))
                $translation = substr_replace($translation, $value, $pos, strlen($needle));
        }

    }

    // Return translation
    return $only_check ? !Base_Functions::IsNullOrEmpty($translation) : $translation;
}

function fill__t($translation, $obj = null, $start = "(", $end = ")", $isTranslation = true) {

    // Get the template by the translation
    $template = $isTranslation ? __t($translation) : $translation;

    if (!Base_Functions::IsNullOrEmpty($template)) {

        // Check and replace dynamic variables
        if(!Base_Functions::IsNullOrEmpty($obj)) {

            // Check if the obj is a string
            if (is_string($obj)) {
                
                // Check if has (
                if(Base_Functions::HasSubstring($template, "(")) {

                    // Get params
                    $params = Base_Functions::getTextInTags($template, "(", ")");

                    // Check if has params
                    $param = count($params) > 0 ? $params[0] : "";

                    // Replace
                    $template = substr_replace($template, $obj, strpos($template, "($param)"), strlen("($param)"));
                }
            }
            else {

                // Replace template variables
                foreach ($obj as $key => $value) {
                    $key = $start . $key . $end;
        
                    if(Base_Functions::HasSubstring($template, $key))
                        $template = str_replace($key, $value, $template);
        
                    elseif(Base_Functions::HasSubstring($template, strtoupper($key)))
                        $template = str_replace(strtoupper($key), $value, $template);
        
                    elseif(Base_Functions::HasSubstring($template, strtolower($key)))
                        $template = str_replace(strtolower($key), $value, $template);
                }
            }

            // Take placeholders that have not been replaced
            $placeholders = Base_Functions::getPlaceholders($template);
        
            // Check if placeholders exist
            if(!Base_Functions::IsNullOrEmpty($placeholders)) {
        
                // Cycle the placeholders
                foreach($placeholders as $placeholder) {
                    // Set the key
                    $key = $start . $placeholder . $end;
        
                    $template = str_replace($key, "", $template);
                }
            }
        }
    }
    else 
        $template = "";
    
    return $template;
}