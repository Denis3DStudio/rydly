<?php

    namespace App\News;

    use stdClass;
    use Base_Methods;
    use Base_Functions;
    use Base_Automatic_Mail;
    use Base_Cache;
    use Mails_Labels;

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
            public function get($idNews) {
                
                $idLanguage = $this->Logged->IdLanguage;

                // Get the news 
                $news = $this->__linq->fromDB("news")->whereDB("IdNews = $idNews AND IsDeleted = 0 AND Status = 1")->getFirstOrDefault();

                // Get categories translations
                $sql = "SELECT cn.IdNews, ct.IdCategory, ct.Title, c.Color
                        FROM categories_news cn
                        INNER JOIN categories c ON c.IdCategory = cn.IdCategory 
                        INNER JOIN categories_translations ct 
                            ON cn.IdCategory = ct.IdCategory AND ct.IdLanguage = {$idLanguage}
                        WHERE cn.IdNews = $idNews
                        ORDER BY ct.IsValid DESC, ct.IdLanguage ASC";

                $news_categories = $this->__linq->queryDB($sql)->getResults();

                $news_translations = $this->__linq->fromDB("news_translations")->whereDB("IdNews = $idNews AND IdLanguage = $idLanguage")->getResults();

                // Get news images
                $news_images = $this->__linq->selectDB("IdNews, FullPath, FileName")->fromDB("news_images")->whereDB("IdNews = $idNews ORDER BY OrderNumber ASC")->getResults();
                
                if(!Base_Functions::IsNullOrEmpty($news))
                    return $this->Success($this->formatNews($news, $news_translations, $news_images, $news_categories, false));

                return $this->Not_Found();
            }
            public function getAll($limit = null) {
                
                $idLanguage = $this->Logged->IdLanguage;
                
                // Get the request
                $request = $this->Request;

                // Check if the variables exist in the $request obj
                $search_query = property_exists($request, "search") ? "AND (nt.Title LIKE '%{$request->search}%' OR DATE_FORMAT(n.Date, '%d/%m/%Y') LIKE '%$request->search%')" : "";
                
                // Set the basic where
                $basic_where = "(t1)IsValid = 1 AND (t1)IsDeleted = 0 AND (t1)Status = 1";

                // Set the category where
                if (property_exists($request, "IdsCategories") && !Base_Functions::IsNullOrEmpty($request->IdsCategories)) {

                    // Convert in int
                    $IdsCategories = array_map('intval', $request->IdsCategories);
                    // Convert in string
                    $IdsCategories = implode(",", $IdsCategories);

                    $categories_where = " AND (SELECT GROUP_CONCAT(cn.IdCategory ORDER BY cn.IdCategory)
                                        FROM categories_news cn
                                        WHERE cn.IdNews = n.IdNews AND cn.IdCategory IN($IdsCategories)) > 0";
                }
                else
                    $categories_where = "";

                // Create the body query (for search and count the new filtered)
                $body_query = "FROM news n
                               INNER JOIN news_translations nt ON n.IdNews = nt.IdNews AND nt.IdLanguage = {$idLanguage}
                               WHERE " . str_replace("(t1)", "n.", $basic_where) . $categories_where;

                $limit = !Base_Functions::IsNullOrEmpty($limit) ? "LIMIT $limit" : "";

                // Get the news
                $sql = "SELECT n.*
                        $body_query
                        $search_query
                        ORDER BY n.Date DESC
                        $limit";

                $news = $this->__linq->queryDB($sql)->getResults();

                // Init the response array
                $response = array();

                // Check that the news is not null
                if (!Base_Functions::IsNullOrEmpty($news)) {

                    // Get the ids of the news
                    $ids_news = implode(", ", array_column($news, "IdNews"));

                    // Get content translations
                    $news_translations = $this->__linq->fromDB("news_translations")->whereDB("IdNews IN ($ids_news) AND IdLanguage = $idLanguage ORDER BY IsValid DESC, IdLanguage ASC")->getResults();
                    
                    // Get news images
                    $news_images = $this->__linq->selectDB("IdNews, FullPath, FileName")->fromDB("news_images")->whereDB("IdNews IN ($ids_news) ORDER BY OrderNumber ASC")->getResults();

                    // Get categories translations
                    $sql = "SELECT cn.IdNews, ct.IdCategory, ct.Title, c.Color
                            FROM categories_news cn
                            INNER JOIN categories c ON c.IdCategory = cn.IdCategory 
                            INNER JOIN categories_translations ct 
                                ON cn.IdCategory = ct.IdCategory AND ct.IdLanguage = {$idLanguage}
                            ORDER BY ct.IsValid DESC, ct.IdLanguage ASC";

                    $news_categories = $this->__linq->reorder($this->__linq->queryDB($sql)->getResults(), "IdNews", true);

                    // Cycle all news
                    foreach($news as $new) {

                        // Push the format data in the response array
                        array_push($response, $this->formatNews($new, $news_translations, $news_images, property_exists($news_categories, $new->IdNews) ? $news_categories->{$new->IdNews} : null, true));
                    }
                }

                // Return the response
                return $this->Success($response);
            }
        
            // Post 
            public function requestToWrite($name, $surname, $email, $message) {

                Base_Automatic_Mail::createMail(Mails_Labels::NEWS_WRITE_REQUEST, $name, $surname, $email, $message);

                return $this->Success();
            }
        #endregion

        #region Private Methods

            private function formatNews($news, $news_translations = null, $news_images = null, $news_categories = null, $isAll = false) {

                // Reorder
                $news_translations = $this->__linq->reorder($news_translations, "IdNews", true);
                $news_images = $this->__linq->reorder($news_images, "IdNews", true);

                // Build object
                $response = new stdClass();
                $response->IdNews = $news->IdNews;
                $response->Author = $news->Author ?? '-';
                $response->Date = Base_Functions::FormatDate("d/m/Y", $news->Date);
                $response->Status = $news->Status;
                $response->Title = (property_exists($news_translations, $news->IdNews)) ? $news_translations->{$news->IdNews}[0]->Title : '';
                $response->Subtitle = (property_exists($news_translations, $news->IdNews)) ? $news_translations->{$news->IdNews}[0]->Subtitle : '';
                $response->Category = null;

                // Check if the news_categories is not null
                if (!Base_Functions::IsNullOrEmpty($news_categories))
                    $response->Category = $news_categories[0];

                if( $isAll) {
                    $response->ImageUrl = (property_exists($news_images, $news->IdNews)) ? $this->checkImagePath($news_images->{$news->IdNews}[0]->FullPath) : $this->checkImagePath(null);
                } else {

                    $response->Description = (property_exists($news_translations, $news->IdNews)) ? $news_translations->{$news->IdNews}[0]->Description : '';

                    $response->Images = array();

                    // check if news images is not null
                    if (property_exists($news_images, $news->IdNews)) {
                        
                        // Cycle all news images
                        foreach($news_images->{$news->IdNews} as $image) {
                            array_push($response->Images, $this->checkImagePath($image->FullPath));
                        }
                    }

                    $places = array_column($this->__linq->fromDB("news_places")->whereDB("IdNews = $news->IdNews")->getResults(), "IdPlace");
                    // format places
                    $response->Places = count($places) > 0 ? $this->place->getAll([], [], [], $places)->Places : [];
                }
                
                return $response;
            }         

        #endregion


        

    }