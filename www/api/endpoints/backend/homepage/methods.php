<?php

    namespace Backend\Homepage;

    use stdClass;
    use Base_File;
    use Base_Path;
    use Base_Methods;
    use Base_Functions;
    use Base_Homepage_Banner;
    use Base_Homepage_Banner_Color;
    use Base_Homepage_Banner_Position;

    class Methods extends Base_Methods {

        #region Constructors-Destructors
            public function __construct() {
                parent::__construct();
            }
            public function __destruct() {
            }      
        #endregion
        
        #region Public Methods

            #region Banner

                // Get
                public function getBanner($idBanner) {

                    // Get the banner
                    $banner = $this->__linq->fromDB("homepage_banners")->whereDB("IdHomepageBanner = $idBanner AND IsDeleted = 0")->getFirstOrDefault();

                    // Check if the banner exists
                    if (!Base_Functions::IsNullOrEmpty($banner)) {

                        // Get the translations of the banner
                        $banner->Languages = $this->__linq->selectDB("Title, Description, ButtonLabel, ButtonLink, IdLanguage")->fromDB("homepage_banners_translations")->whereDB("IdHomepageBanner = $idBanner AND IsValid = 1")->getResults();

                        return $this->Success($banner);
                    }

                    return $this->Not_Found();
                }
                public function getBanners($type) {

                    // Get all the banners of the type
                    $banners = $this->__linq->fromDB("homepage_banners")->whereDB("Type = $type AND IsValid = 1 AND IsDeleted = 0 ORDER BY OrderNumber")->getResults();

                    // Check if the banners exists
                    if (!Base_Functions::IsNullOrEmpty($banners)) {

                        // Get the banners ids 
                        $banners_ids = implode(", ", array_column($banners, "IdHomepageBanner"));

                        // Get the translations of the banner
                        $banners_translations = $this->__linq->reorder($this->__linq->selectDB("IdHomepageBanner, Title, Description, ButtonLabel, ButtonLink, IdLanguage")->fromDB("homepage_banners_translations")->whereDB("IdHomepageBanner IN ($banners_ids) AND IsValid = 1 ORDER BY IdLanguage ASC")->getResults(), "IdHomepageBanner", true);

                        // Cycle all the banners
                        foreach ($banners as $banner) {
                            $banner->IdHomepageBanner = $banners_translations->{$banner->IdHomepageBanner}[0]->IdHomepageBanner;
                            $banner->Title = $banners_translations->{$banner->IdHomepageBanner}[0]->Title;
                            $image = $this->__linq->selectDB("FullPath")->fromDB("homepage_banners_images")->whereDB("IdHomepageBanner = $banner->IdHomepageBanner")->getFirstOrDefault();
                            if ($image != null) {
                                $banner->FullPath = $image->FullPath;
                            } else {
                                $banner->FullPath = null;
                            }
                            
                            if ($type == Base_Homepage_Banner::BANNER_IMAGE) {
                                // Get the value of the translations
                                $banner->ButtonLink = $banners_translations->{$banner->IdHomepageBanner}[0]->ButtonLink;
                                $banner->IdLanguage = $banners_translations->{$banner->IdHomepageBanner}[0]->IdLanguage;
                            }
                        }

                    }

                    return $this->Success($banners);
                }

                // Post
                public function createBanner($type) {

                    $header_banner_type = Base_Homepage_Banner::BANNER_HEADER;

                    // Check the type
                    if ($type == $header_banner_type) {

                        // Get all the banners of the header valid
                        $header_banners = $this->__linq->fromDB("homepage_banners")->whereDB("Type = $header_banner_type AND IsValid = 1 AND IsDeleted = 0")->getResults();

                        // Check if there are banners
                        if (!Base_Functions::IsNullOrEmpty($header_banners))
                            return $this->Internal_Server_Error(null, "Header banner già presente, eliminare prima quello attuale");
                    }

                    // Create the obj for the banner
                    $banner = new stdClass();
                    $banner->Type = $type;

                    $idBanner = $this->__opHelper->object($banner)->table("homepage_banners")->insert();

                    // Return the id
                    return $this->Success($idBanner);
                }   

                // Put
                public function updateBanner() {

                    // Get the request
                    $request = $this->Request;

                    // Get the banner 
                    $this->getBanner($request->IdBanner);

                    // Check if is Success
                    if ($this->Success == true) {

                        // Create the obj to update the banner
                        $banner = new stdClass();
                        $banner->IdHomepageBanner = $request->IdBanner;
                        $banner->Name = $request->Name;
                        $banner->IsValid = 1;

                        // Update the banner
                        $this->__opHelper->object($banner)->table("homepage_banners")->where("IdHomepageBanner")->update();

                        // Get all the translations of the banner and reoder by IdLanguage
                        $translations = $this->__linq->reorder($this->__linq->fromDB("homepage_banners_translations")->whereDB("IdHomepageBanner = $request->IdBanner")->getResults(), "IdLanguage");

                        // Cycle all the languages
                        foreach ($request->Languages as $language) {

                            // Check if the language exists
                            if (property_exists($translations, $language->IdLanguage)) {

                                // Create the obj to update the translations
                                $translation = new stdClass();
                                $translation->ButtonLink = $language->ButtonLink;
                                $translation->IdHomepageBannerTranslation = $translations->{$language->IdLanguage}->IdHomepageBannerTranslation;

                                // Update the translations
                                $this->__opHelper->object($translation)->table("homepage_banners_translations")->where("IdHomepageBannerTranslation")->update();
                            }
                            else {

                                // Create the obj to update the translations
                                $translation = new stdClass();
                                $translation->IdHomepageBanner = $request->IdBanner;
                                $translation->ButtonLink = !property_exists($language, "ButtonLink") ? null : $language->ButtonLink;
                                $translation->IsValid = 1;
                                $translation->IdLanguage = $language->IdLanguage;

                                // Insert the translations
                                $this->__opHelper->object($translation)->table("homepage_banners_translations")->insert();
                            }
                        }

                        return $this->Success();
                    }
                    
                    return $this->Not_Found();
                }
                public function reorderSlider() {

                    // Cicle all the banners
                    foreach($this->Request->Order as $singleBanner) {

                        // Build the obj for the ordering 
                        $obj = new stdClass();
                        $obj->IdHomepageBanner = $singleBanner->IdHomepageBanner;
                        $obj->OrderNumber = $singleBanner->OrderNumber;
                        $this->__opHelper->object($obj)->table("homepage_banners")->where("IdHomepageBanner")->update();

                    }
                    return $this->Success();
                }

                // Delete
                public function deleteBanner($idBanner) {

                    // Get the banner
                    $this->getBanner($idBanner);

                    // Check if is Success
                    if ($this->Success == true) {

                        // Get the images of the banner
                        $images = $this->getBannerImages($idBanner);

                        // Check if the images exists
                        if (count($images) > 0) {

                            // Cycle all the images
                            foreach ($images as $image) {

                                // Delete the image
                                $this->deleteBannerImage($image->IdHomepageBannerImage);
                            }
                        }

                        // Create the obj to delete the banner
                        $delete = new stdClass();
                        $delete->IdHomepageBanner = $idBanner;

                        // Delete the banner
                        $this->__opHelper->object($delete)->table("homepage_banners")->where("IdHomepageBanner")->delete();

                        // Delete the banner translations
                        $this->__opHelper->object($delete)->table("homepage_banners_translations")->where("IdHomepageBanner")->delete();

                        // Delete the banner images
                        $this->__opHelper->object($delete)->table("homepage_banners_images")->where("IdHomepageBanner")->delete();

                        return $this->Success();
                    }

                    return $this->Not_Found();
                }

                #region Images

                    // Get
                    public function getBannerImage($idHomepageBannerImage) {

                        // Get the image
                        $image = $this->__linq->fromDB("homepage_banners_images")->whereDB("IdHomepageBannerImage = $idHomepageBannerImage")->getFirstOrDefault();

                        // Check if the image exists
                        if (!Base_Functions::IsNullOrEmpty($image))
                            return $this->Success($image);

                        return $this->Not_Found();
                    }
                    public function getBannerImages($idBanner) {

                        // Get the images
                        $images = $this->__linq->fromDB("homepage_banners_images")->whereDB("IdHomepageBanner = $idBanner")->getResults();
                        
                        return $this->Success($images);
                    }

                    // Post
                    public function saveBannerImage() {

                        // Get the request
                        $request = $this->Request;

                        // Check if is success
                        if ($this->Success == true) {

                            // Get the files
                            $files = $_FILES;

                            // Get the old image of the 
                            $old_image = $this->__linq->fromDB("homepage_banners_images")->whereDB("TypeImage = $request->Type AND IdHomepageBanner = $request->IdBanner")->getFirstOrDefault();

                            // Check that the $old_image is not null
                            if (!Base_Functions::IsNullOrEmpty($old_image))
                                $this->deleteBannerImage($old_image->IdHomepageBannerImage);

                            // Check if the file is not null
                            if(!Base_Functions::IsNullOrEmpty($files)) {
                        
                                // Create path
                                $file_path = str_replace("(*)", $request->IdBanner, Base_Path::HOMEPAGE_BANNER_IMAGES);
                
                                // Create path if not exists
                                if (!file_exists($_SERVER["DOCUMENT_ROOT"] . $file_path))
                                    mkdir($_SERVER["DOCUMENT_ROOT"] . $file_path, 0755, true);
                
                                // Get files number
                                $files_number = count($files["Files"]["name"]);
                            
                                // Upload files
                                for ($i=0; $i < $files_number; $i++) { 
                
                                    // Create file
                                    $f = new stdClass();
                                    $f->name = Base_Functions::FileName($files["Files"]["name"][$i]);
                                    $f->type = $files["Files"]["type"][$i];
                                    $f->tmp_name = $files["Files"]["tmp_name"][$i];
                                    $f->error = $files["Files"]["error"][$i];
                                    $f->size = $files["Files"]["size"][$i];
                
                                    // Save file
                                    $file_manage = new Base_File($file_path);
                                    $file = $file_manage->offRoot(false)
                                                        ->file((array)$f)
                                                        ->autoResize(true)
                                                        ->save()[0];
                                                        
                                    // Create the obj to update
                                    $file->IdHomepageBanner = $request->IdBanner;
                                    $file->TypeImage = $request->Type;

                                    // Add the images in the category_images
                                    $this->__opHelper->object($file)->table("homepage_banners_images")->insert();
                                }
                                
                                return $this->Success();   
                            }
                        }

                        return $this->Not_Found();
                    }

                    // Delete
                    public function deleteBannerImage($idHomepageBannerImage) {

                        // Get the content
                        $content = $this->getBannerImage($idHomepageBannerImage);

                        // Check if is valid
                        if($this->Success == true) {

                            // Create path
                            $path = $_SERVER["DOCUMENT_ROOT"] . $content->FullPath;
                            // Create folder path
                            $folder_path = str_replace($content->FileName, "", $path);

                            // check if exists
                            if(file_exists($path)) 
                                // delete file
                                unlink($path);
                            
                            if(is_dir($folder_path) && count(scandir($folder_path)) == 2)
                                rmdir($folder_path);

                            // Delete the row
                            $this->__opHelper->object($content)->table("homepage_banners_images")->where("IdHomepageBannerImage")->delete();

                            return $this->Success();
                        }

                        return $this->Not_Found();
                    }

                #endregion

            #endregion

        #endregion
    }

?>