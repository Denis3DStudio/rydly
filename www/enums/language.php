<?php

    class Base_Languages {

        const DEFAULT = self::ITALIAN;

        const ALL = [self::ITALIAN, self::ENGLISH, self::SPANISH, self::FRENCH, self::GERMAN];

        const ITALIAN = 1;
        const ENGLISH = 2;
        const SPANISH = 3;
        const FRENCH = 4;
        const GERMAN = 5;

        const ABBREVIATIONS = [
            self::ITALIAN => "it",
            self::ENGLISH => "en",
            self::SPANISH => "es",
            self::FRENCH => "fr",
            self::GERMAN => "de",
        ];
        const IDS = [
            "it" => self::ITALIAN,
            "en" => self::ENGLISH,
            "es" => self::SPANISH,
            "fr" => self::FRENCH,
            "de" => self::GERMAN,
        ];
        const URL_ABBREVIATIONS = [
            self::ITALIAN => "/",
            self::ENGLISH => "/en/",
            self::SPANISH => "/es/",
            self::FRENCH => "/fr/",
            self::GERMAN => "/de/",
        ];

        const DEEPL_ABBREVIATIONS = [
            self::ITALIAN => "IT",
            self::ENGLISH => "EN-GB",
            self::SPANISH => "ES",
            self::FRENCH => "FR",
            self::GERMAN => "DE",
        ];
        const DEEPL_ABBREVIATIONS_SOURCE = [
            self::ITALIAN => "IT",
            self::ENGLISH => "EN",
            self::SPANISH => "ES",
            self::FRENCH => "FR",
            self::GERMAN => "DE",
        ];

        const PAYPAL_CODE = [
            self::ITALIAN => "it-IT",
            self::ENGLISH => "en-US",
        ];

        const SELECT_PICKER_CODE = [
            self::ITALIAN => "it_IT",
            self::ENGLISH => "en_US",
        ];

        const NAMES = [
            self::ITALIAN => "Italiano",
            self::ENGLISH => "Inglese",
            self::SPANISH => "Spagnolo",
            self::FRENCH => "Francese",
            self::GERMAN => "Tedesco",
        ];

        const TRANSLATIONS = [
            self::ITALIAN => "BACKEND.LANGUAGE.ITALIAN",
            self::ENGLISH => "BACKEND.LANGUAGE.ENGLISH",
        ];

        public function getCurrentLanguage() {

            $response = self::DEFAULT;
            // Get current domain
            $domain = $_SERVER['SERVER_NAME'];
    
            // Explode by .
            $domain = explode(".", $domain);
    
            // Explode request uri
            $request = array_values(array_filter(explode("/", $_SERVER['REQUEST_URI'])));
    
            // Check if the $request is not null
            if (count($request) > 0)
                $response = self::getLanguageFromAbbreviation($request[0]);
    
            return $response;
        }

        public static function getLanguageFromAbbreviation($abbreviation = "") {

            // Check if the abbreviation exists in the IDS array
            $abbreviation = strtolower($abbreviation);
            foreach (self::ABBREVIATIONS as $key => $value) {
                if ($value == $abbreviation)
                    return $key;
            }

            return self::DEFAULT;
        }
    }

?>