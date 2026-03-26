<?php

    namespace Backend\News;

    use stdClass;
    use Base_File;
use Base_Files_Path;
use Base_Path;
    use Base_Methods;
    use Base_Functions;
    use Base_Files;
    use Base_Files_Types;

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
            public function get($idNews, $isValid = 0) {

                // Set the value of the where
                $where = ($isValid == 1) ? "AND IsValid = 1" : "";

                // Get the news
                $news = $this->__linq->fromDB("news")->whereDB("IdNews = $idNews AND IsDeleted = 0 $where")->getFirstOrDefault();

                $news->Categories = $this->__linq->selectDB('IdCategory')->fromDB("categories_news")->whereDB("IdNews = $idNews")->getResults();
                
                $news->Places = $this->__linq->selectDB('IdPlace')->fromDB("news_places")->whereDB("IdNews = $idNews")->getResults();
                
                if(!Base_Functions::IsNullOrEmpty($news))
                    return $this->Success($this->formatNews($news));

                return $this->Not_Found();
            }
            public function getAll() {

                // Get the request
                $request = $this->Request;

                // Check if the variables exist in the $request obj
                $search_query = property_exists($request, "search_query") ? $request->search_query : "";
                $order_by_query = property_exists($request, "order_by_query") ? $request->order_by_query : "";
                $limit_query = property_exists($request, "limit_query") ? $request->limit_query : "LIMIT 10";
                
                // Set the basic where
                $basic_where = "(t1)IsValid = 1 AND (t1)IsDeleted = 0";

                // Set the category where
                if (!Base_Functions::IsNullOrEmpty($request->IdsCategories)) {

                    // Convert in int
                    $IdsCategories = array_map('intval', $request->IdsCategories);
                    // Sort the array ASC
                    sort($IdsCategories);
                    // Convert in string
                    $IdsCategories = implode(",", $IdsCategories);

                    $categories_where = " AND (SELECT GROUP_CONCAT(cn.IdCategory ORDER BY cn.IdCategory)
                                        FROM categories_news cn
                                        WHERE cn.IdNews = n.IdNews AND cn.IdCategory IN($IdsCategories)) = '$IdsCategories'";
                }
                else
                    $categories_where = "";

                // Create the body query (for search and count the new filtered)
                $body_query = "FROM news n
                               INNER JOIN news_translations nt ON n.IdNews = nt.IdNews AND nt.IdLanguage = (SELECT MIN(nt2.IdLanguage)
                                                                                                            FROM news_translations nt2
                                                                                                            WHERE nt2.IdNews = n.IdNews
                                                                                                            )
                               WHERE " . str_replace("(t1)", "n.", $basic_where) . $categories_where;

                // Get the news
                $sql = "SELECT n.*
                        $body_query
                        $search_query
                        $order_by_query $limit_query";

                $news = $this->__linq->queryDB($sql)->getResults();

                // Count the total news
                $total_count = $this->__linq->selectDB("COUNT(*) AS Total")->fromDB("news")->whereDB(str_replace("(t1)", "", $basic_where))->getFirstOrDefault()->Total;

                // Check if the search query is not empty
                if (!Base_Functions::IsNullOrEmpty($search_query) || !Base_Functions::IsNullOrEmpty($request->IdsCategories)) {

                    // Count the filtered news
                    $sql = "SELECT COUNT(*) AS Total
                            $body_query
                            $search_query";

                    $filtered_count = $this->__linq->queryDB($sql)->getFirstOrDefault()->Total;
                }
                else
                    $filtered_count = $total_count;

                // Init the response array
                $response = array();

                // Check that the news is not null
                if (!Base_Functions::IsNullOrEmpty($news)) {

                    // Get the ids of the news
                    $ids_news = implode(", ", array_column($news, "IdNews"));

                    // Get content translations
                    $news_translations = $this->__linq->fromDB("news_translations")->whereDB("IdNews IN ($ids_news) ORDER BY IsValid DESC, IdLanguage ASC")->getResults();
                    
                    // Get categories translations
                    $sql = "SELECT cn.IdNews, ct.IdCategory, ct.Title
                            FROM categories_news cn
                            INNER JOIN categories_translations ct ON cn.IdCategory = ct.IdCategory
                            WHERE cn.IdNews IN ($ids_news)
                            ORDER BY ct.IsValid DESC, ct.IdLanguage ASC";

                    $news_categories = $this->__linq->reorder($this->__linq->queryDB($sql)->getResults(), "IdNews");

                    // Get news images
                    $news_images = $this->__linq->selectDB("IdNews, FullPath, FileName")->fromDB("news_images")->whereDB("IdNews IN ($ids_news) ORDER BY OrderNumber ASC")->getResults();

                    // Cycle all news
                    foreach($news as $new) {

                        // Get the categories of the news
                        $new_categories = property_exists($news_categories, $new->IdNews) ? $news_categories->{$new->IdNews} : null;

                        // Push the format data in the response array
                        array_push($response, $this->formatNews($new, $news_translations, $news_images, $new_categories, true));
                    }
                }

                // Add recordsTotal and recordsFiltered to the response for the datatable server side
                $this->ServerSideTotalCount = $total_count;
                $this->ServerSideFilteredCount = $filtered_count;

                // Return the response
                return $this->Success($response);
            }

            // Post
            public function create() {

                // Create a new row in the news table
                $idNews = $this->__opHelper->object("IdNews")->table("news")->insertIncrement();

                // Check if created
                if(is_numeric($idNews) && $idNews > 0)
                    return $this->Success($idNews);

                return $this->Internal_Server_Error();
            }
 
            // Put
            public function update() {

                // Get the request
                $request = $this->Request;

                // Check if the news exists
                $this->get($request->IdNews);

                // Check if is success
                if ($this->Success == true) {

                    // Get the languages
                    $languages = $request->Languages;

                    // Remove Languages from request
                    unset($request->Languages);

                    // Add IsValid to 1
                    $request->IsValid = 1;

                    // Delete all the categories of the news
                    $obj = new stdClass();
                    $obj->IdNews = $request->IdNews;
                    $this->__opHelper->object($obj)->table("categories_news")->where("IdNews")->delete();

                    // Update category if not -1
                    if(!in_array('-1', $request->Category)) {
                        $values = '';
                        $fields = '(IdNews, IdCategory)';
                        foreach($request->Category as $category) {
                            $values .= "({$request->IdNews}, $category), ";
                        }
                        $values = rtrim($values, ', ');
                        
                        $this->__opHelper->table("categories_news")->insertMassive($fields, $values);
                    }
                    
                    // Delete all the categories of the news
                    $obj = new stdClass();
                    $obj->IdNews = $request->IdNews;
                    $this->__opHelper->object($obj)->table("news_places")->where("IdNews")->delete();

                    // Update places if not -1
                    if(!in_array('-1', $request->Place)) {
                        $values = '';
                        $fields = '(IdNews, IdPlace)';
                        foreach($request->Place as $place) {
                            $values .= "({$request->IdNews}, $place), ";
                        }
                        $values = rtrim($values, ', ');
                        
                        $this->__opHelper->table("news_places")->insertMassive($fields, $values);
                    }
                   
                    
                    $this->__opHelper->object($request)->table("news")->where("IdNews")->update();

                    // Init the array for the translation values
                    $translation_values = array();

                    // Cycle all languages
                    foreach ($languages as $language) {

                        // Slug the title
                        $slugUrl = Base_Functions::Slug($language->Title) . "-" . $request->IdNews;
                        
                        $obj = new stdClass();
                        $obj->IdNews = $request->IdNews;
                        $obj->Title = $language->Title;
                        $obj->Subtitle = $language->Subtitle ?? "";
                        $obj->Description = $language->Description;
                        $obj->SlugUrl = $slugUrl;
                        $obj->IdLanguage = $language->IdLanguage;
                        $obj->IsValid = 1;

                        $obj = Base_Functions::convertForMassive($obj, true);
                        // Create query
                        $query = "(" . implode(", ", array_values((array)$obj)) . ")";

                        array_push($translation_values, $query);
                    }

                    // Delete the old translations
                    $sql = "DELETE FROM news_translations WHERE IdNews = $request->IdNews";
                    $this->__linq->queryDB($sql)->getResults();

                    // Insert the languages
                    $this->__opHelper->table("news_translations")->insertMassive("(IdNews, Title, Subtitle, Description, SlugUrl, IdLanguage, IsValid)", implode(", ", $translation_values));

                    return $this->Success();
                }

                return $this->Not_Found();
            }

            // Delete
            public function delete($idNews) {

                // Check that the news exists
                $this->get($idNews);

                // Check if success 
                if ($this->Success == true) {

                    // Update the news to deleted
                    $obj = new stdClass();
                    $obj->IdNews = $idNews;
                    $obj->IsDeleted = 1;

                    // Update
                    $this->__opHelper->object($obj)->table("news")->where("IdNews")->update();

                    return $this->Success();
                }

                return $this->Not_Found();
            }

            #region Link

                // Get
                public function getLinks($idNews, $idLanguage) {

                    // Get all link of the news of that language
                    $links = $this->__linq->fromDB("news_links")->whereDB("IdNews = $idNews AND IdLanguage = $idLanguage ORDER BY OrderNumber ASC")->getResults();

                    return $this->Success($links);
                }

                // Post
                public function saveLink() {

                    // Get the request
                    $request = $this->Request;

                    // Check that the news is not null
                    $this->get($request->IdNews);

                    // Check if is success
                    if ($this->Success == true) {

                        // Set the order number
                        $request->OrderNumber = 1;

                        // Insert the link in the table
                        $this->__opHelper->object($request)->table("news_links")->insert();

                        return $this->Success();
                    }

                    return $this->Not_Found();
                }

                // Put
                public function updateLinksOrder() {

                    // Get the request
                    $request = $this->Request;

                    // Check that the news is not null
                    $this->get($request->IdNews);

                    // Check if is success
                    if ($this->Success == true) {
    
                        // Cycle the Order array
                        foreach($request->Order as $news) {

                            // Update the news_links table
                            $this->__opHelper->object($news)->table("news_links")->where("IdLink")->update();
                        }

                        return $this->Success(null, "Ordine salvato con successo!");
                    }

                    return;
                }

                // Delete
                public function deleteLink() {

                    // Get the request
                    $request = $this->Request;

                    // Check that the news is not null
                    $this->get($request->IdNews);

                    // Check if is success
                    if ($this->Success == true) {

                        // Set the id link
                        $obj = new stdClass();
                        $obj->IdLink = $request->IdLink;

                        // Delete the row
                        $this->__opHelper->object($obj)->table("news_links")->where("IdLink")->delete();

                        return $this->Success();
                    }

                    return $this->Not_Found();
                }

            #endregion

            #region Attachments

                // Get
                public function getAttachment($idAttachment) {

                    // Get the attachments by the id
                    $attachment = $this->__linq->fromDB("news_attachments")->whereDB("IdAttachment = $idAttachment")->getFirstOrDefault();

                    // Check if the $attachment is not null 
                    if (!Base_Functions::IsNullOrEmpty($attachment))
                        return $this->Success($attachment);

                    return $this->Not_Found();
                }
                public function getAttachments($idNews, $idLanguage) {

                    // Get all attachments of the news of that language
                    $attachments = $this->__linq->fromDB("news_attachments")->whereDB("IdNews = $idNews AND IdLanguage = $idLanguage ORDER BY OrderNumber ASC")->getResults();

                    return $this->Success($attachments);
                }

                // Post
                public function saveAttachments() {

                    // Get the request
                    $request = $this->Request;

                    // Check that the news is not null
                    $this->get($request->IdNews);

                    // Check if is success
                    if ($this->Success == true) {

                        // Build th extra
                        $extra = new stdClass();
                        $extra->IdLanguage = $request->IdLanguage;
                
                        // Save the files
                        Base_File::saveContentManager($request->IdNews, Base_Files::NEWS, Base_Files_Types::ATTACHMENT, $extra);
                        
                        return $this->Success();   
                    }

                    return $this->Not_Found();
                }

                // Put
                public function updateAttachmentsOrder() {

                    // Get the request
                    $request = $this->Request;

                    // Check that the news is not null
                    $this->get($request->IdNews);

                    // Check if is success
                    if ($this->Success == true) {
    
                        // Cycle the Order array
                        foreach($request->Order as $news) {

                            // Create the update object
                            $obj = new stdClass();
                            $obj->IdAttachment = $news->id;
                            $obj->OrderNumber = $news->order_number;

                            // Update the news_attachments table
                            $this->__opHelper->object($obj)->table("news_attachments")->where("IdAttachment")->update();
                        }

                        return $this->Success();
                    }

                    return $this->Not_Found();
                }

                // Delete
                public function deleteAttachment() {

                    // Get the request
                    $request = $this->Request;

                    // Check that the news is not null
                    $this->get($request->IdNews);

                    // Check if is success
                    if ($this->Success == true) {

                        // Delete the file
                        if(!Base_File::deleteContentManager($request->IdAttachment, Base_Files::NEWS, Base_Files_Types::ATTACHMENT))
                            return $this->Not_Found();

                        return $this->Success();
                    }

                    return $this->Not_Found();
                }

            #endregion

            #region Contents

                // Get
                public function getContent() {

                    // Get the request
                    $request = clone($this->Request);

                    // Check that the news exists
                    $this->get($request->IdNews);

                    // Check that is success
                    if ($this->Success) {

                        // Check the ContentType
                        if ($request->ContentType == 1)
                            // Image
                            $response = $this->__linq->fromDB("news_images")->whereDB("IdImage = $request->ContentRefId")->getFirstOrDefault();
                        else
                            // Video
                            $response = $this->__linq->fromDB("news_videos")->whereDB("IdVideo = $request->ContentRefId")->getFirstOrDefault();

                        // Check it the $response is not null
                        if (!Base_Functions::IsNullOrEmpty($response))
                            return $this->Success($response);
                    }

                    return $this->Not_Found();
                }
                public function getContents($idNews) {

                    $sql = "SELECT *
                            FROM (

                                SELECT ni.IdImage AS Id, ni.FullPath AS Preview, " . Base_Files_Types::IMAGE . " AS Type, ni.OrderNumber
                                FROM news_images ni
                                WHERE ni.IdNews = $idNews

                                UNION ALL

                                SELECT nv.IdVideo AS Id, CONCAT('https://img.youtube.com/vi/', nv.VideoCode, '/hqdefault.jpg') as Preview, " . Base_Files_Types::VIDEO . " as Type, nv.OrderNumber
                                FROM news_videos nv
                                WHERE nv.IdNews = $idNews

                            ) t
                            ORDER BY OrderNumber ASC";

                    // Get the contents
                    $contents = $this->__linq->queryDB($sql)->getResults();
                    
                    return $this->Success($contents);
                }

                // Put
                public function updateContentsOrder() {

                    // Get the request
                    $request = $this->Request;

                    // Check that the news is not null
                    $this->get($request->IdNews);

                    // Check if is success
                    if ($this->Success == true) {
    
                        // Cycle the Order array
                        foreach($request->Order as $news) {

                            // Check the type of the news (Custom property)
                            if ($news->Type == 2) {
                                // Image

                                // Create the update object
                                $obj = new stdClass();
                                $obj->IdImage = $news->Id;
                                $obj->OrderNumber = $news->OrderNumber;

                                // Update the news_attachments table
                                $this->__opHelper->object($obj)->table("news_images")->where("IdImage")->update();
                            }
                            else {
                                // Video

                                // Create the update object
                                $obj = new stdClass();
                                $obj->IdVideo = $news->Id;
                                $obj->OrderNumber = $news->OrderNumber;

                                // Update the news_attachments table
                                $this->__opHelper->object($obj)->table("news_videos")->where("IdVideo")->update();
                            }
                        }

                        return $this->Success();
                    }

                    return $this->Not_Found();
                }   

                // Delete
                public function deleteContent() {

                    // Get the request
                    $request = clone($this->Request);

                    // Delete the file
                    if(!Base_File::deleteContentManager($request->ContentRefId, Base_Files::NEWS,  $request->ContentType == 2 ? Base_Files_Types::VIDEO : Base_Files_Types::IMAGE))
                        return $this->Not_Found();

                    return $this->Success();
                }

                #region Images

                    // Post
                    public function saveImages() {

                        // Get the request
                        $request = $this->Request;

                        // Check that the news is not null
                        $this->get($request->IdNews);

                        // Check if is success
                        if ($this->Success == true) {

                            // Save the images
                            Base_File::saveContentManager($request->IdNews, Base_Files::NEWS, Base_Files_Types::IMAGE);

                            return $this->Success();
                        }

                        return $this->Not_Found();
                    }

                #endregion

                #region Videos

                    // Post
                    public function saveVideo() {

                        // Get the request
                        $request = $this->Request;

                        // Check that the news is not null
                        $this->get($request->IdNews);

                        // Check if is success
                        if ($this->Success == true) {

                            // Set the order number
                            $request->OrderNumber = 1;

                            // Insert the link in the table
                            $this->__opHelper->object($request)->table("news_videos")->insert();

                            return $this->Success();
                        }

                        return $this->Not_Found();
                    }

                #endregion

                #region Caption

                    // Get
                    public function getCaption() {

                        // Get the request
                        $request = $this->Request;

                        // Check that the news exists
                        $this->get($request->IdNews);

                        // Check if success
                        if ($this->Success == true) {

                            // Get the caption
                            $captions = $this->__linq->selectDB("IdLanguage, Caption")->fromDB("news_captions")->whereDB("IdNews = $request->IdNews AND ContentRefId = $request->ContentRefId AND ContentType = $request->ContentType")->getResults();

                            // Create the response obj
                            $response = new stdClass();
                            $response->ContentRefId = $request->ContentRefId;
                            $response->ContentType = $request->ContentType;
                            $response->Languages = array();

                            // Check if the captions is not null
                            if (count($captions) > 0) {

                                // Cycle all captions
                                foreach ($captions as $caption) {

                                    // Create the obj of the caption
                                    $obj = new stdClass();
                                    $obj->IdLanguage = $caption->IdLanguage;
                                    $obj->Caption = $caption->Caption;

                                    // Push the obj in the languages array
                                    array_push($response->Languages, $obj);
                                }
                            }

                            return $this->Success($response);
                        }

                        return $this->Not_Found();
                    }

                    // Put
                    public function saveCaption() {

                        // Get the request
                        $request_obj = clone($this->Request);
    
                        $caption = $this->getCaption();

                        // Check if is success
                        if($this->Success == true) {

                            // Reorder the captions by the IdLanguage
                            $captions = $this->__linq->reorder($caption->Languages, "IdLanguage");

                            // Cycle all languages
                            foreach($request_obj->Languages as $language) {

                                // Check if the caption is not null
                                if (!Base_Functions::IsNullOrEmpty($language->Caption)) {

                                    $obj = new stdClass();
                                    $obj->Caption = $language->Caption;

                                    // Check if the language exists
                                    if (property_exists($captions, $language->IdLanguage)) {

                                        // Update the caption if is different
                                        if ($captions->{$language->IdLanguage}->Caption != $language->Caption)
                                            $this->__opHelper->object($obj)->table("news_captions")->update("IdNews = $request_obj->IdNews AND ContentRefId = $request_obj->ContentRefId AND ContentType = $request_obj->ContentType AND  IdLanguage = $language->IdLanguage");
                                    }
                                    else {

                                        // Add the IdNews, ContentRefId, ContentType and IdLanguage
                                        $obj->IdNews = $request_obj->IdNews;
                                        $obj->ContentRefId = $request_obj->ContentRefId;
                                        $obj->ContentType = $request_obj->ContentType;
                                        $obj->IdLanguage = $language->IdLanguage;

                                        // Insert the caption
                                        $this->__opHelper->object($obj)->table("news_captions")->insert();
                                    }
                                }
                                else {

                                    // Delete the caption
                                    $sql = "DELETE FROM news_captions  WHERE IdNews = $request_obj->IdNews AND ContentRefId = $request_obj->ContentRefId AND ContentType = $request_obj->ContentType AND  IdLanguage = $language->IdLanguage";
                                    $this->__linq->queryDB($sql)->getResults();
                                }
                            }

                            return $this->Success();
                        }

                        return $this->Not_Found();
                    }

                #endregion
                    
            #endregion

            #region Jobs

                public function deleteFilesAfter365days() {

                    // Create the expiration date
                    $expiration_date = date('Y-m-d', strtotime('- 365 days'));

                    // Get the news Update more than 30 days and having IsDeleted = 1
                    $news = $this->__linq->fromDB("news")->whereDB("IsDeleted = 1 AND DATE_FORMAT(UpdateDate, '%Y-%m-%d') = '$expiration_date'")->getResults();

                    // Check that the news array is not null
                    if (count($news) > 0) {

                        // Get the idNews array
                        $ids_news = array_column($news, "IdNews");

                        // Get the id news string
                        $ids_news_string = implode(", ", $ids_news);    

                        // Delete from attachments table
                        $sql = "DELETE FROM news_attachments WHERE IdNews IN ($ids_news_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // Delete from captions table
                        $sql = "DELETE FROM news_captions WHERE IdNews IN ($ids_news_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // Delete from images table
                        $sql = "DELETE FROM news_images WHERE IdNews IN ($ids_news_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // Delete from links table
                        $sql = "DELETE FROM news_links WHERE IdNews IN ($ids_news_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // Delete from translations table
                        $sql = "DELETE FROM news_translations WHERE IdNews IN ($ids_news_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // Delete from videos table
                        $sql = "DELETE FROM news_videos WHERE IdNews IN ($ids_news_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // delete from news places table
                        $sql = "DELETE FROM news_places WHERE IdNews IN ($ids_news_string)";
                        $this->__linq->queryDB($sql)->getResults();

                        // Cycle all news
                        foreach ($ids_news as $id_news) {

                            // Delete all files of the news
                            Base_Functions::deleteFiles($_SERVER["DOCUMENT_ROOT"] . Base_Path::NEWS . $id_news);
                        }
                    }
                }

            #endregion

        #endregion

        #region Private Methods

            private function formatNews($news, $news_translations = null, $news_images = null, $news_categories = null, $isAll = false) {

                // Get content translations
                if($news_translations == null)
                    $news_translations = $this->__linq->fromDB("news_translations")->whereDB("IdNews = $news->IdNews")->getResults();
                // get Image
                if($news_images == null)
                    $news_images = $this->__linq->selectDB("IdNews, FullPath, FileName")->fromDB("news_images")->whereDB("IdNews = $news->IdNews")->getResults();

                // Reorder
                $news_translations = $this->__linq->reorder($news_translations, "IdNews", true);
                $news_images = $this->__linq->reorder($news_images, "IdNews", true);

                // Build object
                $response = new stdClass();
                $response->IdNews = $news->IdNews;
                $response->Author = $news->Author;
                $response->Date = $news->Date;
                $response->Status = $news->Status;
                $response->Title = array();
                $response->LanguagesIds = array();
                $response->Image = (property_exists($news_images, $news->IdNews)) ? $news_images->{$news->IdNews}[0] : array();

                if(!$isAll) {
                    $response->Languages = array();
                    $response->Categories = !Base_Functions::IsNullOrEmpty($news->Categories) ? array_column($news->Categories, 'IdCategory') : '-1';
                    $response->Places = !Base_Functions::IsNullOrEmpty($news->Places) ? array_column($news->Places, 'IdPlace') : '-1';
                }
                else {
                    
                    $response->Categories = array();

                    // Check if the news_categories is not null
                    if (!Base_Functions::IsNullOrEmpty($news_categories)) {

                        // reorder by category
                        $news_categories = $this->__linq->reorder($news_categories, "IdCategory", true);

                        // Cycle all new categories
                        foreach ($news_categories as $news_category) {
                            // Push the first translation of the category
                            array_push($response->Categories, $news_category[0]->Title);
                        }
                    }
                }
                
                // Check translations
                if(property_exists($news_translations, $news->IdNews)) {

                    if ($isAll) {

                        // Get translations
                        $response->Title = $news_translations->{$news->IdNews}[0]->Title;
                        $response->LanguagesIds = array_column($news_translations->{$news->IdNews}, "IdLanguage");
                    }   
                    else {

                        // Get translations
                        $news_translations = $news_translations->{$news->IdNews};

                        foreach ($news_translations as $translation) {
    
                            // Create the obj of the translation
                            $obj = new stdClass();
                            $obj->Title = $translation->Title;
                            $obj->Subtitle = $translation->Subtitle;
                            $obj->Description = $translation->Description;
                            $obj->IsValid = $translation->IsValid;
                            $obj->IdLanguage = $translation->IdLanguage;
                            if($isAll)
                                $obj->Categories = $this->getCategoryByLang($response->IdNews, $obj->IdLanguage);
                            
                            // Push the obj in the languages array
                            array_push($response->Languages, $obj);
                        }
                    }
                }
                
                return $response;
            }
            private function getCategoryByLang($idNews, $language) {
                $response = '';

                $categories = array_column($this->__linq->selectDB('IdCategory')->fromDB("categories_news")->whereDB("IdNews = $idNews")->getResults(), 'IdCategory');

                if(count($categories) > 0) {
                    foreach($categories as $category) {
                        $categoryName = $this->__linq->selectDB('Name')->fromDB("categories_translations")->whereDB("IdCategory = $category AND IdLanguage = $language")->getFirstOrDefault();
                        if(!Base_Functions::IsNullOrEmpty($categoryName))
                            $response .= $categoryName->Name . ', ';
                    }
                } 
                

                return rtrim($response, ', ');
            }            

        #endregion

    }