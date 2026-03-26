<?php

    class Base_Account {

        const ALL = [self::USER, self::ADMIN, self::SUPERADMIN];

        const USER = 0;
        const ADMIN = 3;
        const SUPERADMIN = 4;

        const NAMES = [
            self::USER => "Utente",
            self::ADMIN => "Amministratore",
            self::SUPERADMIN => "Super Amministratore"
        ];

        const COLORS = [
            self::USER => "dark",
            self::ADMIN => "warning",
            self::SUPERADMIN => "danger"
        ];
    }

?>