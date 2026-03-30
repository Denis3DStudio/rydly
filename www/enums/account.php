<?php

    class Base_Account {

        const ALL = [
            self::ORGANIZATION, 
            self::ORGANIZATION_ADMIN, 
            self::SUPERADMIN
        ];

        const USER_THAT_CAN_MANAGE = [
            self::ORGANIZATION_ADMIN, 
            self::SUPERADMIN
        ];

        const ROLES_WITH_ORGANIZATION = [
            self::ORGANIZATION, 
            self::ORGANIZATION_ADMIN
        ];

        const ROLES_CAN_SEE = [
            self::ORGANIZATION => [
                self::ORGANIZATION
            ],
            self::ORGANIZATION_ADMIN => [
                self::ORGANIZATION, 
                self::ORGANIZATION_ADMIN
            ],
            self::SUPERADMIN => self::ALL
        ];

        const ORGANIZATION = 2;
        const ORGANIZATION_ADMIN = 3;
        const SUPERADMIN = 4;

        const NAMES = [
            self::ORGANIZATION => "Organizzatore",
            self::ORGANIZATION_ADMIN => "Amministratore Organizzatore",
            self::SUPERADMIN => "Super Amministratore"
        ];

        const COLORS = [
            self::ORGANIZATION => "info",
            self::ORGANIZATION_ADMIN => "warning",
            self::SUPERADMIN => "danger"
        ];
    }

?>