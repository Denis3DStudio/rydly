<?php

    class Base_Homepage_Banner {

        const BANNER_HEADER = 1; // the banner in the header (only one)
        const BANNER_IMAGE = 2; // the banners in the homepage with images (multiple)
    }

    class Base_Homepage_Banner_Color {

        const ALL = [self::BLACK, self::WHITE];

        const BLACK = 1;
        const WHITE = 2;

        const NAMES = [
            self::BLACK => "Nero",
            self::WHITE => "Bianco"
        ];
    }

    class Base_Homepage_Banner_Position {

        const ALL = [self::LEFT, self::RIGHT, self::CENTER];

        const LEFT = 1;
        const RIGHT = 2;
        const CENTER = 3;

        const NAMES = [
            self::LEFT => "Sinista",
            self::RIGHT => "Destra",
            self::CENTER => "Centrale"
        ];
    }   

    class Base_Homepage_Banner_Image_Type {

        const ALL = [self::DESKTOP, self::MOBILE];

        const DESKTOP = 1;
        const MOBILE = 2;
    }   
?>