<?php

    class Base_Files {

        const ALL = [self::NEWS];

        const NEWS = 0;
        const PRODUCT = 1;
        const CATEGORY = 2;
        const PROJECT = 3;
        const PROJECT_SINGLE = 4;
        const EMAIL = 5;
        const PLACE_CATEGORY = 6;
        const PLACE = 7;
        const SPONSOR = 8;

        const IDS_DB = [
            self::NEWS => 'IdNews',
            self::PRODUCT => 'IdProduct',
            self::CATEGORY => 'IdCategory',
            self::PROJECT => 'IdProject',
            self::PROJECT_SINGLE => 'IdProject',
            self::EMAIL => 'IdEmail',
            self::PLACE_CATEGORY => 'IdCategory',
            self::PLACE => 'IdPlace',
            self::SPONSOR => 'IdSponsor'
        ];

        const DB_TABLES_NAMES = [
            self::NEWS => 'news',
            self::PRODUCT => 'products',
            self::CATEGORY => 'categories',
            self::PROJECT => 'projects',
            self::PROJECT_SINGLE => 'projects_singles',
            self::EMAIL => 'emails',
            self::PLACE_CATEGORY => 'categories_places',
            self::PLACE => 'places',
            self::SPONSOR => 'sponsors',
        ];

        const DB_TABLES_CAPTIONS_NAMES = [
            self::NEWS => 'news_captions',
            self::PRODUCT => 'products_captions',
        ];

        const CHANGE_FILE_NAME = [
        ];

        const FOLDER_NAMES = [
            self::NEWS => 'news',
            self::PRODUCT => 'products',
            self::CATEGORY => 'categories',
            self::PROJECT => 'projects',
            self::PROJECT_SINGLE => 'projects',
            self::EMAIL => 'emails',
            self::PLACE_CATEGORY => 'categories_places',
            self::PLACE => 'places',
            self::SPONSOR => 'sponsors',
        ];
    }

    class Base_Files_Types {

        const ALL = [self::GENERIC, self::ATTACHMENT, self::IMAGE];

        const GENERIC = 0;
        const ATTACHMENT = 1;
        const IMAGE = 2;
        const VIDEO = 3;

        const FOLDER_NAMES = [
            self::GENERIC => '',
            self::ATTACHMENT => 'attachments/',
            self::IMAGE => 'images/',
            self::VIDEO => 'videos/',
        ];

        const DB_TABLES_NAMES = [
            self::GENERIC => '',
            self::ATTACHMENT => 'attachments',
            self::IMAGE => 'images',
            self::VIDEO => 'videos',
        ];

        const DB_IDS_TYPES = [
            self::GENERIC => "",
            self::ATTACHMENT => "IdAttachment",
            self::IMAGE => "IdImage",
            self::VIDEO => "IdVideo",
        ];

        const CONTENT_IDS = [
            self::IMAGE => 1,
            self::VIDEO => 2,
            self::ATTACHMENT => 3,
        ];

        const ACTIONS = [
            self::IMAGE => Base_Files_Actions::ALL,
            self::VIDEO => Base_Files_Actions::ALL,
            self::ATTACHMENT => Base_Files_Actions::ALL,
        ];

        const EXTENSIONS = [
            self::IMAGE => ['jpg', 'jpeg', 'png', 'gif'],
            self::ATTACHMENT => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'],
        ];
    }

    class Base_Files_Path {

        const ALL = [self::BASIC, self::ADVANCED];

        const BASIC = 0;
        const ADVANCED = 1;

        const NAMES = [
            self::BASIC => '/contents/{{FOLDER_NAME}}/',
            self::ADVANCED => '/contents/{{FOLDER_NAME}}/{{ID_ROW}}/{{TYPE_NAME}}',
        ];
    }

    class Base_Files_Upload_Type {

        const ALL = [self::ON_CLICK, self::ON_CHANGE];

        const ON_CLICK = 0;
        const ON_CHANGE = 1;

        const NAMES = [
            self::ON_CLICK => 'onclick',
            self::ON_CHANGE => 'onchange',
        ];
    }

    class Base_Files_Actions {

        const ALL = [self::EDIT, self::DELETE, self::SHOW];

        const EDIT = 1;
        const DELETE = 2;
        const SHOW = 3;
    }

    class Base_Files_Extentions {

        const ALL = [self::IMAGE, self::FILE, self::GENERIC];

        const IMAGE = 1;
        const FILE = 2;
        const GENERIC = 3;

        const NAMES = [
            self::IMAGE => ['jpg', 'jpeg', 'png', 'gif'],
            self::FILE => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'],
        ];
    }

    class Base_Files_Captions_Types {

        const ALL = [self::MONO_LANG, self::MULTI_LANG];

        const MONO_LANG = 0;
        const MULTI_LANG = 1;

        const NAMES = [
            self::MONO_LANG => 'MonoLang',
            self::MULTI_LANG => 'MultiLang',
        ];

        const BUTTONS = [
            self::MONO_LANG => 'templateButtonCaption-0',
            self::MULTI_LANG => 'templateButtonCaption-1',
        ];

        const TEMPLATES = [
            self::MONO_LANG => 'modalCaptionInputTemplate',
            self::MULTI_LANG => 'modalCaptionLanguageContainerTemplate',
        ];

    }

?>