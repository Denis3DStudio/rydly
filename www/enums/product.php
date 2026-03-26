<?php

    class Base_Product_Variant_Type {

        const ALL = [self::WITH_VARIANTS, self::NO_VARIANTS];

        const WITH_VARIANTS = 1;
        const NO_VARIANTS = 0;

        const NAMES = [
            self::WITH_VARIANTS => "Con varianti",
            self::NO_VARIANTS => "Senza varianti",
        ];
    }

    class Base_Attribute_Type {

        const ALL = [self::TEXT, self::COLOR];

        const TEXT = 0;
        const COLOR = 1;

        const NAMES = [
            self::COLOR => "Colore",
            self::TEXT => "Testo",
        ];
    }

    class Base_Attribute_Value_Color {

        const ONE_COLOR = 1;
        const MORE_COLORS = 2;

        const DEFAULT = self::ONE_COLOR;
    }

?>