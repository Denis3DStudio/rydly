<?php

    class Base_Survey_Question_Type {

        const ALL = [
            // self::FREE,
            self::SINGLE_CHOICE,
            self::MULTIPLE_CHOICE
        ];

        const FREE = 1;
        const SINGLE_CHOICE = 2;
        const MULTIPLE_CHOICE = 3;

        const NAMES = [
            self::FREE => "Libera",
            self::SINGLE_CHOICE => "Scelta Singola",
            self::MULTIPLE_CHOICE => "Scelta Multipla"
        ];

        const TRANSLATIONS = [
            self::SINGLE_CHOICE => "APP.TRAVELER_PATH.SINGLE_CHOICE",
            self::MULTIPLE_CHOICE => "APP.TRAVELER_PATH.MULTIPLE_CHOICE"
        ];

        const COLORS = [
            self::FREE => "primary",
            self::SINGLE_CHOICE => "success",
            self::MULTIPLE_CHOICE => "warning"
        ];
    }

?>