<?php

    class Base_Account {

        const ALL = [
            self::ORGANIZER, 
            self::ORGANIZER_ADMIN, 
            self::SUPERADMIN
        ];

        const USER_THAT_CAN_MANAGE = [
            self::ORGANIZER_ADMIN, 
            self::SUPERADMIN
        ];

        const ROLES_WITH_ORGANIZER = [
            self::ORGANIZER, 
            self::ORGANIZER_ADMIN
        ];

        const ROLES_CAN_SEE = [
            self::ORGANIZER => [
                self::ORGANIZER
            ],
            self::ORGANIZER_ADMIN => [
                self::ORGANIZER, 
                self::ORGANIZER_ADMIN
            ],
            self::SUPERADMIN => self::ALL
        ];

        const ORGANIZER = 2;
        const ORGANIZER_ADMIN = 3;
        const SUPERADMIN = 4;

        const NAMES = [
            self::ORGANIZER => "Organizzatore",
            self::ORGANIZER_ADMIN => "Amministratore Organizzatore",
            self::SUPERADMIN => "Super Amministratore"
        ];

        const COLORS = [
            self::ORGANIZER => "info",
            self::ORGANIZER_ADMIN => "warning",
            self::SUPERADMIN => "danger"
        ];
    }

?>