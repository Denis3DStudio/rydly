<?php

    class Base_Account {

        const ALL = [
            self::ORGANIZATION, 
            self::ORGANIZATION_ADMIN, 
            self::ADMIN,
            self::SUPERADMIN
        ];

        const ROLE_THAT_CAN_CREATE = [
            self::ORGANIZATION => [
            ],
            self::ORGANIZATION_ADMIN => [
                self::ORGANIZATION, 
                self::ORGANIZATION_ADMIN
            ],
            self::ADMIN => [
                self::ORGANIZATION, 
                self::ORGANIZATION_ADMIN,
                self::ADMIN
            ],
            self::SUPERADMIN => self::ALL
        ];

        const ADMINS = [
            self::ADMIN,
            self::ORGANIZATION_ADMIN, 
            self::SUPERADMIN
        ];

        const FULL_ACCESS = [
            self::ADMIN, 
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
        const ADMIN = 1;

        const NAMES = [
            self::ADMIN => "Amministratore",
            self::ORGANIZATION => "Organizzatore",
            self::ORGANIZATION_ADMIN => "Amministratore Organizzatore",
            self::SUPERADMIN => "Super Amministratore"
        ];

        const COLORS = [
            self::ADMIN => "danger",
            self::ORGANIZATION => "info",
            self::ORGANIZATION_ADMIN => "warning",
            self::SUPERADMIN => "danger"
        ];

        public static function isOrganizationMember($idRole) {
            return in_array($idRole, self::ROLES_WITH_ORGANIZATION);
        }
    }

?>