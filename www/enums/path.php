<?php

    class Base_Path {

        const API = ACTIVE_PATH;

        const NEWS = "/contents/news/";
        const NEWS_ATTACHMENTS = "/contents/news/(*)/attachments/";
        const NEWS_IMAGES = "/contents/news/(*)/images/";

        const PLACE = "/contents/places/";
        const PLACE_ATTACHMENTS = "/contents/places/(*)/attachments/";
        const PLACE_IMAGES = "/contents/places/(*)/images/";

        const PRODUCT = "/contents/products/";
        const PRODUCT_ATTACHMENTS = "/contents/products/(*)/attachments/";
        const PRODUCT_IMAGES = "/contents/products/(*)/images/";
        
        const HOMEPAGE_BANNER = "/contents/homepage/banners/";
        const HOMEPAGE_BANNER_IMAGES = "/contents/homepage/banners/(*)/images/";
        
        const NAMES = [
            "NEWS" => self::NEWS,
            "NEWS_ATTACHMENTS" => self::NEWS_ATTACHMENTS,
            "NEWS_IMAGES" => self::NEWS_IMAGES,
            
            "PRODUCT" => self::PRODUCT,
            "PRODUCT_ATTACHMENTS" => self::PRODUCT_ATTACHMENTS,
            "PRODUCT_IMAGES" => self::PRODUCT_IMAGES,
            
            "HOMEPAGE_BANNER" => self::HOMEPAGE_BANNER,
            "HOMEPAGE_BANNER_IMAGES" => self::HOMEPAGE_BANNER_IMAGES,
        ];


        const FULL = "/contents/{{}}/(*)/attachments/";
    }


?>