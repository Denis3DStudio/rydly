<?php

    class Base_Category_Type {

        const NEWS = 1;
        const PLACE = 2;
        const SPONSOR = 3;
        const ORGANIZER = 4;

        const NAMES = [
            self::NEWS => "news",
            self::PLACE => "places",
            self::SPONSOR => "sponsors",
            self::ORGANIZER => "organizers"
        ];

        const INNER_TABLES = [
            self::NEWS => "news_categories",
            self::PLACE => "places_categories",
            self::SPONSOR => "sponsors_categories",
            self::ORGANIZER => "organizers_categories"
        ];

        const IDS_TO_JOIN = [
            self::NEWS => "IdNews",
            self::PLACE => "IdPlace",
            self::SPONSOR => "IdSponsor",
            self::ORGANIZER => "IdOrganizer"
        ];

        const MAIN_TABLE_INNER = [
            self::NEWS => "news",
            self::PLACE => "places",
            self::SPONSOR => "sponsors",
            self::ORGANIZER => "organizers"
        ];

        const PAGES = [
            self::NEWS => "category/",
            self::PLACE => "category_place/",
            self::SPONSOR => "category_sponsor/",
            self::ORGANIZER => "category_organizer/"
        ];
    }

?>