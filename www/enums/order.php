<?php

    class Base_Payment_Method {

        const ALL = [self::BANK_TRANSFER, self::PAYPAL];
        
        const PAYPAL = 1; // PayPal / credit card
        const BANK_TRANSFER = 2; // Bonifico bancario
        const MARK = 3; // Contrassegno

        const NAMES = [
            self::PAYPAL => "PayPal",
            self::BANK_TRANSFER => "Bonifico",
            self::MARK => "Contrassegno"
        ];
        
        const PRICES = [
            self::PAYPAL => 0,
            self::BANK_TRANSFER => 0,
            self::MARK => 10
        ];
    }

    class Base_Order_Payment_Status {

        const ALL = [self::PENDING_PAYMENT, self::PAID_SUCCESS, self::PAID_FAILED];

        const PENDING_PAYMENT = 1;
        const PAID_SUCCESS = 2;
        const PAID_FAILED = 3;

        const NAMES = [
            self::PENDING_PAYMENT => "WEBSITE.ORDERS.WAITING_PAYMENT",
            self::PAID_SUCCESS => "WEBSITE.ORDERS.PAID",
            self::PAID_FAILED => "WEBSITE.ORDERS.PAID_FAILED",
        ];

        const COLORS = [
            self::PENDING_PAYMENT => "warning",
            self::PAID_SUCCESS => "success",
            self::PAID_FAILED => "danger",
        ];
    }

    class Base_Order_Status {

        const ALL = [self::TAKEN_IN_CHARGE, self::IN_PROCESS, self::SHIPPED, self::DELIVERED];

        const TAKEN_IN_CHARGE = 1;
        const IN_PROCESS = 2;
        const SHIPPED = 3;
        const DELIVERED = 4;

        const NAMES = [
            self::TAKEN_IN_CHARGE => "WEBSITE.ORDERS.TAKEN_IN_CHARGE",
            self::IN_PROCESS => "WEBSITE.ORDERS.IN_PROCCESS",
            self::SHIPPED => "WEBSITE.ORDERS.SHIPPED",
            self::DELIVERED => "WEBSITE.ORDERS.DELIVERED"
        ];

        const COLORS = [
            self::TAKEN_IN_CHARGE => "warning",
            self::IN_PROCESS => "info",
            self::SHIPPED => "primary",
            self::DELIVERED => "success"
        ];
    }
    
    class Base_Return_Status {

        const ALL = [self::IN_ELABORATION, self::COMPLETED, self::REFUSED];

        const IN_ELABORATION = 1;
        const COMPLETED = 2;
        const REFUSED = 3;

        const NAMES = [
            self::IN_ELABORATION => "WEBSITE.RETURN.IN_ELABORATION",
            self::COMPLETED => "WEBSITE.RETURN.COMPLETED",
            self::REFUSED => "WEBSITE.RETURN.REFUSED"
        ];

        const COLORS = [
            self::IN_ELABORATION => "secondary",
            self::COMPLETED => "success",
            self::REFUSED => "danger"
        ];
    }

?>