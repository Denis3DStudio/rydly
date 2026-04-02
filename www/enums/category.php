<?php

    class Base_Category_Type {

        const NEWS = 1;
        const EVENT = 2;
        const SPONSOR = 3;
        const ORGANIZATION = 4;

        const NAMES = [
            self::NEWS => "news",
            self::EVENT => "events",
            self::SPONSOR => "sponsors",
            self::ORGANIZATION => "organizations"
        ];

        const INNER_TABLES = [
            self::NEWS => "news_categories",
            self::EVENT => "events_categories",
            self::SPONSOR => "sponsors_categories",
            self::ORGANIZATION => "organizations_categories"
        ];

        const IDS_TO_JOIN = [
            self::NEWS => "IdNews",
            self::EVENT => "IdEvent",
            self::SPONSOR => "IdSponsor",
            self::ORGANIZATION => "IdOrganization"
        ];

        const MAIN_TABLE_INNER = [
            self::NEWS => "news",
            self::EVENT => "events",
            self::SPONSOR => "sponsors",
            self::ORGANIZATION => "organizations"
        ];

        const PAGES = [
            self::NEWS => "category/",
            self::EVENT => "category_event/",
            self::SPONSOR => "category_sponsor/",
            self::ORGANIZATION => "category_organization/"
        ];
    }

?>