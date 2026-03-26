<?php

    class Base_Customer_Type {

        const ANONYMOUS = 1;
        const LOGGED = 2;

        const EXTERNAL = [
            self::ANONYMOUS => 1,
            self::LOGGED => 2
        ];
    }

    class Base_Customer_Gender {

        const ALL = [self::MALE, self::FEMALE, self::NOT_SPECIFIED];

        const MALE = 1;
        const FEMALE = 2;
        const NOT_SPECIFIED = 3;

        const TRASLATIONS = [
            self::MALE => "WEBSITE.GENDER.MALE",
            self::FEMALE => "WEBSITE.GENDER.FEMALE",
            self::NOT_SPECIFIED => "WEBSITE.GENDER.NOT_SPECIFIED"
        ];
    }
?>