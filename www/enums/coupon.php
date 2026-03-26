<?php

    class Base_Coupon_Type {

        const ALL = [self::MONEY, self::PERCENTAGE, self::FREE_SHIPPING];

        const MONEY = 1;
        const PERCENTAGE = 2;
        const FREE_SHIPPING = 3;

        const NAMES = [
            self::MONEY => "Euro - €",
            self::PERCENTAGE => "Percentuale - %",
            self::FREE_SHIPPING => "Spedizione Gratuita"
        ];

    }

    class Base_Coupon_Cart_Type {

        const FOR_ALL_CART = 1;
        const FOR_PRODUCTS = 2;
        const FOR_SHIPPING = 3;
    }
?>