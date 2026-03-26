<?php

    namespace App\Place;

    use stdClass;
    use Base_Methods;
    use Base_Functions;
    use Base_Automatic_Mail;
    use Base_Cache_Manager;
    use Base_Cache_Names;
    use Mails_Labels;

    class Methods extends Base_Methods {

        private $id = "IdPlace";
        private $table_name = "places";
        private $table_translations_name = "places_translations";
        private $table_images_name = "places_images";

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }
        #endregion
        
        #region Public Methods

            // Get
            public function get($idPlace) {

                $idLanguage = $this->Logged->IdLanguage;

                // Get the place
                $sql = "SELECT tt.Description, tt.SmallDescription, t.* 
                    FROM {$this->table_name} t
                    INNER JOIN {$this->table_translations_name} tt ON tt.{$this->id} = t.{$this->id}
                    WHERE t.IsActive = 1 AND t.IsValid = 1 AND t.IsDeleted = 0 AND t.IdPlace = $idPlace AND tt.IdLanguage = $idLanguage";
                $place = $this->__linq->queryDB($sql)->getFirstOrDefault();

                if (Base_Functions::IsNullOrEmpty($place))
                    return $this->Not_Found(null, "Luogo non trovato!");

                // get place category
                $place_categoreis = $this->getPlaceCategories($idPlace,$idLanguage);

                // get place images
                $place_images = $this->__linq->selectDB("FullPath")->fromDB($this->table_images_name)->whereDB("$this->id = $idPlace ORDER BY OrderNumber ASC")->getResults();

                // get blog
                $sql = "SELECT nt.IdNews, nt.Title, nt.Subtitle as SmallDescription, n.Author, n.Date, ni.FullPath
                    FROM news_places np
                    INNER JOIN news n ON np.IdNews = n.IdNews
                    INNER JOIN news_translations nt ON np.IdNews = nt.IdNews
                    LEFT JOIN news_images ni ON np.IdNews = ni.IdNews AND OrderNumber = 1
                    WHERE np.IdPlace = $idPlace AND nt.IdLanguage = $idLanguage AND n.IsValid = 1 AND n.IsDeleted = 0";
                $blog = $this->__linq->queryDB($sql)->getResults();

                // Get traveler path
                $travelerPath = $this->__linq->selectDB('GROUP_CONCAT(IdSurveyQuestionAnswer) AS IdSurveyQuestionAnswer')->fromDB("surveys_questions_answers_places")->whereDB($this->id . " = $idPlace")->getFirstOrDefault();
                
                $response = new stdClass();
                $response->IsClaimed = $place->IsClaimed;
                $response->Categories = $place_categoreis;
                $response->Name = $place->Name;
                $response->Phone = $place->Phone;
                $response->Address = $place->Address;
                $response->City = $place->City;
                $response->Latitude = $place->Latitude;
                $response->Longitude = $place->Longitude;
                $response->SmallDescription = $place->SmallDescription;
                $response->Description = $place->Description;
                $response->TravelerPath = $travelerPath->IdSurveyQuestionAnswer;
                $response->GoogleMaps = "https://www.google.com/maps/search/$place->Name/@$place->Latitude,$place->Longitude,17z";
                $response->Images = array();
                if ($place_images) {
                    foreach($place_images as $image) {
                        array_push($response->Images, $image->FullPath);
                    }
                }
                $response->News = $blog;

                // Check IfFavorite
                $response->IsFavorite = false;
                if ($this->Logged) {
                    $idCustomer = $this->Logged->IdAccount;
                    $favourite = $this->__linq->fromDB("customers_favorites_places")->whereDB("IdCustomer = $idCustomer AND IdPlace = $idPlace")->getFirstOrDefault();
                    if (!Base_Functions::IsNullOrEmpty($favourite))
                        $response->IsFavorite = true;
                }                

                return $this->Success($response);

            }
            public function getAll($idsCategories = [], $keyword = null, $idsTravelersPaths = [], $idsPlacesParams = [], $myMap = false) {

                // Get language
                $idLanguage = $this->Logged->IdLanguage;

                if (Base_Functions::IsNullOrEmpty($idsPlacesParams)) {
                    
                    // Initialize variables
                    $join = "";
                    $where = "";
                    $inner_join_travelers_paths = "";
                    $inner_join_my_map = "";
                    $where_travelers_paths = "";
                    $group = "";

                    // Check if category or keyword is set
                    if (!Base_Functions::IsNullOrEmpty($idsCategories) && is_array($idsCategories) && count($idsCategories) > 0) {
    
                        $join = "INNER JOIN categories_places_parents cpp ON cpp.{$this->id} = t.{$this->id}";
                        $where = "cpp.IdCategory IN (" . implode(", ", $idsCategories) . ") AND ";
    
                    } else if (!Base_Functions::IsNullOrEmpty($keyword))
                        $where = "(t.Name LIKE \"%$keyword%\" OR t.Address LIKE \"%$keyword%\") AND ";
    
                    // Check if travelers paths are set
                    if (!Base_Functions::IsNullOrEmpty($idsTravelersPaths) && is_array($idsTravelersPaths) && count($idsTravelersPaths) > 0) {

                        // Get the idsTravelersPaths grouped by question
                        $questions = $this->__linq->reorder(
                            $this->__linq->fromDB("surveys_questions_answers")
                                ->whereDB("IdSurveyQuestionAnswer IN (" . implode(",", $idsTravelersPaths) . ")")
                                ->getResults(),
                            "IdSurveyQuestion",
                            true
                        );

                        $orBetweenQuestions = [];

                        // Cycle all questions
                        foreach ($questions as $questionAnswers) {

                            // Get the list of answers for the current question
                            $inList = implode(",", array_column($questionAnswers, "IdSurveyQuestionAnswer"));

                            // AND tra risposte della stessa domanda => HAVING COUNT = N
                            $orBetweenQuestions[] = "
                                EXISTS (
                                    SELECT 1
                                    FROM surveys_questions_answers_places sqap2
                                    WHERE sqap2.IdPlace = t.IdPlace
                                    AND sqap2.IdSurveyQuestionAnswer IN ($inList)
                                    GROUP BY sqap2.IdPlace
                                )
                            ";
                        }

                        // Between questions => OR
                        if (!Base_Functions::IsNullOrEmpty($orBetweenQuestions))
                            $where_travelers_paths = "AND (" . implode(" AND ", $orBetweenQuestions) . ")";

                    }
    
                    // Check if my map is set
                    if ($myMap)
                        $inner_join_my_map = "INNER JOIN customers_favorites_places cmp ON cmp.IdPlace = t.IdPlace AND cmp.IdCustomer = {$this->Logged->IdAccount}";
    
                    // Get all places
                    $sql = "SELECT t.IdPlace
                        FROM {$this->table_name} t
                        INNER JOIN {$this->table_translations_name} tt ON tt.{$this->id} = t.{$this->id}
                        $join
                        $inner_join_travelers_paths
                        $inner_join_my_map
                        WHERE $where t.IsActive = 1 AND t.IsValid = 1 AND t.IsDeleted = 0 AND tt.IdLanguage = $idLanguage $where_travelers_paths
                        $group
                        ORDER BY t.Name";
    
                    $ids_places = array_column($this->__linq->queryDB($sql)->getResults(), "IdPlace");
                }
                else {

                    // Get all places by ids
                    $sql = "SELECT t.IdPlace
                        FROM {$this->table_name} t
                        INNER JOIN {$this->table_translations_name} tt ON tt.{$this->id} = t.{$this->id}
                        WHERE t.IdPlace IN (" . implode(", ", $idsPlacesParams) . ") AND t.IsActive = 1 AND t.IsValid = 1 AND t.IsDeleted = 0 AND tt.IdLanguage = $idLanguage
                        ORDER BY t.Name";

                    $ids_places = array_column($this->__linq->queryDB($sql)->getResults(), "IdPlace");
                }

                // Check if get OnlyCount
                $returnCount = property_exists($this->Request, "OnlyCount") && $this->Request->OnlyCount;

                // Build response
                $response = new stdClass();
                $response->Places = [];
                $response->FavoritesPlaces = [];

                // Check if there are places
                if (Base_Functions::IsNullOrEmpty($ids_places) && !$returnCount)
                    return $this->Success($response);
                else if ($returnCount)
                    return $this->Success(count($ids_places));

                // Get all places from cache
                $cache_places = Base_Cache_Manager::getCache(Base_Cache_Names::PLACES_ALL);
                $cache_places_categories = Base_Cache_Manager::getCache(Base_Cache_Names::PLACES_CATEGORIES_ALL);

                // Get all favorite places of the user
                $response->FavoritesPlaces = array_column($this->__linq->fromDB("customers_favorites_places")->whereDB("IdCustomer = {$this->Logged->IdAccount}")->getResults(), "IdPlace");

                $response->Places = array_values(array_filter(array_map(function ($place) use ($cache_places_categories, $response) {

                    $place->Image = $this->checkImagePath($place->Image);
                    $place->CategoryImage = null;
                    $place->IsFavorite = in_array($place->IdPlace, $response->FavoritesPlaces);

                    // Check if the place has a main category and if it has an image
                    if (property_exists($cache_places_categories, $place->MainCategory)) {

                        $place->CategoryImage = $cache_places_categories->{$place->MainCategory}->FullPath;
                    }
                    else
                        return null;

                    $categories = array();

                    foreach ($place->Categories as $category) {
                        
                        // Check if the category exists in the cache and if it has an image
                        if (property_exists($cache_places_categories, $category)) {

                            $place_category = $cache_places_categories->{$category};

                            // Check if the category ha the translation in the current language
                            if (property_exists($place_category->Translations, $this->Logged->IdLanguage)) {

                                $place_category->Name = $place_category->Translations->{$this->Logged->IdLanguage};
                                
                                $temp = (clone $place_category);
                                unset($temp->Translations);

                                array_push($categories, $temp);
                            }
                        }
                    }

                    if (count($categories) == 0)
                        return null;

                    $place->Categories = $categories;
                    $place->GoogleMaps = $place->UseOnlyCoordinates == 0
                                         ? "https://www.google.com/maps/search/$place->Name/@$place->Latitude,$place->Longitude,17z"
                                         : "https://www.google.com/maps/search/?api=1&query={$place->Latitude},{$place->Longitude}&zoom=17";

                    return $place;

                }, array_filter($cache_places, function($place) use ($ids_places) {
                    return in_array($place->IdPlace, $ids_places);
                }))));

                // Return response
                return $this->Success($response);
            }

            // post
            public function claimRequest($name, $surname, $email, $place) {
                Base_Automatic_Mail::createMail(Mails_Labels::PLACE_CLAIM_REQUEST, $name, $surname, $email, $place);

                return $this->Success();
            }
            public function SuggestRequest($email, $placeName, $city, $message) {
                Base_Automatic_Mail::createMail(Mails_Labels::PLACE_SUGGEST_REQUEST, $email, $placeName, $city, $message);

                return $this->Success();
            }

            #region Favourite

                public function toogleFavorite() {

                    // Check if logged
                    if(!$this->Logged)
                        return $this->Not_Found();

                    // Get request
                    $request = $this->Request;

                    // Get IdCustomer
                    $idCustomer = $this->Logged->IdAccount;

                    // Get if the place is already favourite
                    $favourite = $this->__linq->fromDB("customers_favorites_places")->whereDB("IdCustomer = $idCustomer AND IdPlace = $request->IdPlace")->getFirstOrDefault();

                    // Check if the place is already favourite
                    if ($request->IsFavorite && !Base_Functions::IsNullOrEmpty($favourite))
                        return $this->Not_Found(null, "Il luogo è già tra i preferiti!");

                    // Create or delete favourite
                    if ($request->IsFavorite) {

                        // Create tmp object
                        $tmp = new stdClass();
                        $tmp->IdCustomer = $idCustomer;
                        $tmp->IdPlace = $request->IdPlace;
                        $this->__opHelper->object($tmp)->table("customers_favorites_places")->insert();

                    } else if (!$request->IsFavorite && !Base_Functions::IsNullOrEmpty($favourite))
                        $this->__opHelper->object($favourite)->table("customers_favorites_places")->where("IdFavorite")->delete();

                    // Return response
                    return $this->Success();                    
                }

        #endregion

        #region Private Methods

            private function getPlaceCategories($idPlace,$idLanguage) {
                
                $sql = "SELECT cpt.Title, cpt.IdCategory, cpi.FullPath AS Image
                    FROM categories_places_parents cpp
                    INNER JOIN categories_places_translations cpt ON cpp.IdCategory = cpt.IdCategory
                    INNER JOIN categories_places_images cpi ON cpp.IdCategory = cpi.IdCategory
                    WHERE cpp.IdPlace = $idPlace AND cpt.IdLanguage = $idLanguage
                    ORDER BY cpt.Title";
                $categories = $this->__linq->queryDB($sql)->getResults();
                return $categories;
            }

        #endregion
    }

?>