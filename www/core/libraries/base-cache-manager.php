<?php

class Base_Cache_Manager {

    public static $IdLanguage = null;

    #region News

        public static function setPlacesAllCache() {

            $linq = new Base_LINQHelper();

            // Get all the places
            $places = $linq->selectDB("IdPlace, Name, Latitude, Longitude, UseOnlyCoordinates")->fromDB("places")->whereDB("IsValid = 1 AND IsDeleted = 0 AND IsActive = 1")->getResults();

            // Check if the places is not null
            if (!Base_Functions::IsNullOrEmpty($places)) {

                $ids_places = array_column($places, "IdPlace");

                // Get all the images of the places, for each place, get the first image
                $sql = "SELECT pi.IdPlace, pi.FullPath
                        FROM places_images pi
                        JOIN (
                            SELECT IdPlace, MIN(OrderNumber) AS min_order
                            FROM places_images
                            GROUP BY IdPlace
                        ) m
                        ON pi.IdPlace = m.IdPlace
                        AND pi.OrderNumber = m.min_order;";

                $images = $linq->reorder($linq->queryDB($sql)->getResults(), "IdPlace");

                // Get all the categories of the places
                $places_categories = $linq->reorder($linq->fromDB("categories_places_parents")->whereDB("IdPlace IN (" . implode(",", $ids_places) . ")")->getResults(), "IdPlace", true);

                foreach ($places as $place) {

                    $place->Image = null;
                    $place->Categories = array();
                    $place->MainCategory = null;

                    // Set the image if exists
                    if (property_exists($images, $place->IdPlace))
                        $place->Image = $images->{$place->IdPlace}->FullPath;

                    // Set the categories if exists
                    if (property_exists($places_categories, $place->IdPlace)) {

                        $place->Categories = array_column($places_categories->{$place->IdPlace}, "IdCategory");

                        // Get the main category of the place
                        $main_category = array_filter($places_categories->{$place->IdPlace}, function ($category) {
                            return $category->IsMain == 1;
                        });
                        $place->MainCategory = !empty($main_category) ? array_values($main_category)[0]->IdCategory : (count($place->Categories) > 0 ? $place->Categories[0]->IdCategory : null);
                    }
                }

                // Set the cache
                self::setCache(Base_Cache_Names::PLACES_ALL, $places);
            }
        }
        public static function setPlacesCategoriesAll() {

            $linq = new Base_LINQHelper();

            $sql = "SELECT cp.IdCategory, cpi.FullPath
                    FROM categories_places cp
                    LEFT JOIN categories_places_images cpi ON cp.IdCategory = cpi.IdCategory
                    WHERE cp.IsValid = 1 AND cp.IsDeleted = 0 AND IsActive = 1
                    ORDER BY cp.OrderNumber ASC";

            $categories = $linq->queryDB($sql)->getResults();

            if (!Base_Functions::IsNullOrEmpty($categories)) {
                
                $ids_categories = array_column($categories, "IdCategory");
                // Get all the translations of the categories
                $categories_translations = $linq->reorder($linq->fromDB("categories_places_translations")->whereDB("IdCategory IN (" . implode(",", $ids_categories) . ")")->getResults(), "IdCategory", true);

                foreach ($categories as $category) {

                    $category->Translations = new stdClass();
                    $translations = property_exists($categories_translations, $category->IdCategory) ? $categories_translations->{$category->IdCategory} : null;

                    if (!Base_Functions::IsNullOrEmpty($translations)) {

                        foreach ($translations as $translation) {
                            $category->Translations->{$translation->IdLanguage} = $translation->Title;
                        }
                    }
                }
            }

            // Set the cache
            self::setCache(Base_Cache_Names::PLACES_CATEGORIES_ALL, $linq->reorder($categories, "IdCategory"));
        }
        public static function setSponsorAllCache() {

            // $linq = new Base_LINQHelper();

            // $sponsors = $linq->selectDB("IdSponsor, Name")->fromDB("sponsors")->whereDB("IsValid = 1 AND IsDeleted = 0 AND IsActive = 1")->getResults();

            // if (!Base_Functions::IsNullOrEmpty($sponsors)) {

            //     $ids_sponsors = array_column($sponsors, "IdSponsor");

            //     // Get all the translations of the sponsors
            //     $sponsors_translations = $linq->reorder($linq->fromDB("sponsors_translations")->whereDB("IdSponsor IN (" . implode(",", $ids_sponsors) . ")")->getResults(), "IdSponsor", true);

            // }

            // // Set the cache
            self::setCache(Base_Cache_Names::SPONSORS_ALL, []);
        }
        public static function setOrganizerAllCache() {

            // // Set the cache
            self::setCache(Base_Cache_Names::ORGANIZERS_ALL, []);
        }

    #endregion

    #region Get / Set Cache

        public static function getCache($cache_name, $idRef = null, $to_callback = true, $id_language = null) {

            $callback = null;

            // Check if to_callback is true
            if (is_bool($to_callback) && $to_callback === true)
                // Get the callback
                $callback = Base_Cache_Names::SETTER[$cache_name];
            else if (is_string($to_callback))
                // Set the callback
                $callback = $to_callback;

            // Set the language
            $language = '';
            if(!Base_Functions::IsNullOrEmpty($id_language))
                $language = Base_Languages::ABBREVIATIONS[$id_language] . "/";

            // Get the cache
            $response = Base_Cache::get(str_replace("//", "/", $language . $cache_name));

            // Check if the cache is null and if the callback is not null
            if ($response === false && !Base_Functions::IsNullOrEmpty($callback)) {

                self::$IdLanguage = $id_language;

                // Check if the idRef is not null
                if (!Base_Functions::IsNullOrEmpty($idRef))
                    // Call the callback
                    self::$callback($idRef);
                else
                    // Call the callback
                    self::$callback();
                
                // Get the cache
                $response = self::getCache($cache_name, $idRef, null, $id_language);
            }

            return $response;
        }
        private static function setCache($cache_name, $value, $id_language = null, $cache_enum = null) {

            // Set the language
            $language = '';
            if(!Base_Functions::IsNullOrEmpty($id_language))
                $language = Base_Languages::ABBREVIATIONS[$id_language] . "/";

            // Check if the cache_enum is null
            if (Base_Functions::IsNullOrEmpty($cache_enum))
                $cache_enum = $cache_name;

            // Check if the cache has an expiration date
            if (array_key_exists($cache_enum, Base_Cache_Names::EXPIRATION))
                // Set the cache
                return Base_Cache::set(str_replace("//", "/", $language . $cache_name), $value, Base_Cache_Names::EXPIRATION[$cache_enum]);
            else
                // Set the cache
                return Base_Cache::set(str_replace("//", "/", $language . $cache_name), $value);
        }
        public static function deleteCache($cache_name, $id_language = null) {

            // Set the language
            $language = '';
            if(!Base_Functions::IsNullOrEmpty($id_language))
                $language = Base_Languages::ABBREVIATIONS[$id_language] . "/";

            // Delete the cache
            return Base_Cache::delete($language . $cache_name);
        }

    #endregion
}

class Base_Cache_Names {

    const PLACES_ALL = "PLACES_ALL";
    const PLACES_CATEGORIES_ALL = "CATEGORIES_PLACES_ALL";
    const SPONSORS_ALL = "SPONSORS_ALL";
    const ORGANIZERS_ALL = "ORGANIZERS_ALL";

    const SETTER = [
        self::PLACES_ALL => "setPlacesAllCache",
        self::PLACES_CATEGORIES_ALL => "setPlacesCategoriesAll",
        self::SPONSORS_ALL => "setSponsorsAllCache",
        self::ORGANIZERS_ALL => "setOrganizersAllCache",
    ];

    // Set the expiration date
    const EXPIRATION = [
    ];
}