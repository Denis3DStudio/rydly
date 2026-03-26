<html>

<head>

    <title><?= $this->renderMetaTitle() ?> <?= SITE_NAME ?></title>
    <meta name="description" content="<?= $this->renderMetaDescription() ?>" />

    <!-- Styles + Head -->
    <?php

    $this->renderStyles();
    $this->renderHeadPages();

    ?>

</head>

<body class="fixed-nav <?= $this->renderBodyClass(); ?>" id="page-top">
    <div class="content-wrapper" id="js-navResize">
        <?php $this->renderBodyPages(); ?>
    </div>

    <script tmp_scripts_erasable>
        <?= $this->renderAPIEnumsJS(); ?>
        Url = JSON.parse(`<?= $this->renderURLEnumsJS(); ?>`);
        Logged = JSON.parse(`<?= $this->renderLoggedJS(); ?>`);
        TRANSLATIONS = <?= json_encode(Translations::TranslationsJSON(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        ENUM = JSON.parse(`<?= $this->renderEnums(); ?>`);
    </script>

    <!-- Foot + Scripts -->
    <?php

    $this->renderFootPages();
    $this->renderScripts();

    ?>

    <input type="hidden" id="is_prod" value="<?= PROD ? 1 : 0 ?>">
    <input type="hidden" id="generic_error_translation_error" value="Errore">
    <input type="hidden" id="generic_error_translation_error_message" value="Qualcosa è andato storto...">
    <input type="hidden" id="error_mandatory_select" value="<?= __t("ERROR.MANDATORY.SELECT", true) ? __t("ERROR.MANDATORY.SELECT") : "Selezione obbligatoria" ?>">
    <input type="hidden" id="error_mandatory_checkbox" value="<?= __t("ERROR.MANDATORY.CHECKBOX", true) ? __t("ERROR.MANDATORY.CHECKBOX") : "Opzione obbligatoria" ?>">
    <input type="hidden" id="error_mandatory_date" value="<?= __t("ERROR.MANDATORY.DATE", true) ? __t("ERROR.MANDATORY.DATE") : "Data obbligatoria" ?>">
    <input type="hidden" id="error_mandatory_field" value="<?= __t("ERROR.MANDATORY.FIELD", true) ? __t("ERROR.MANDATORY.FIELD") : "Campo obbligatorio" ?>">
    <input type="hidden" id="error_fill_correct_email" value="<?= __t("ERROR.FILL_CORRECT.EMAIL", true) ? __t("ERROR.FILL_CORRECT.EMAIL") : "Inserisci un'email valida" ?>">
    <input type="hidden" id="error_fill_correct_phone" value="<?= __t("ERROR.FILL_CORRECT.PHONE", true) ? __t("ERROR.FILL_CORRECT.PHONE") : "Inserire un numero di telefono valido" ?>">

    <input type="hidden" id="active_type_platform" value="<?= ltrim(ACTIVE_PATH, "/") ?>">
</body>

</html>