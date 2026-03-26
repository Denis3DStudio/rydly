<?php

    class Base_Keys {

        const BACKEND_PATH = "backend";
        const FRONTEND_PATH = "frontend";
    }

    class Base_Keys_Folder {

        const BACKEND = 1;
        const FRONTEND = 2;

        const NAMES = [
            self::BACKEND => "backend",
            self::FRONTEND => "frontend"
        ];

        const TRANSLATION_SECTION = [
            self::BACKEND => "BACKEND",
            self::FRONTEND => "FRONTEND"
        ];
    }

?>