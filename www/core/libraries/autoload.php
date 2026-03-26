<?php

    // Init files to exclude
    $exclude = [];

    // Include base router custom
    if(file_exists(ACTIVE_FULL_PATH . '/routing/base-router-custom.php'))
        include_once(ACTIVE_FULL_PATH . '/routing/base-router-custom.php');
    else
        array_push($exclude, 'base-router.php');
    
    // Include libraries manually
    include_once(LIBRARIES_PATH . '/base-functions.php');
    include_once(LIBRARIES_PATH . '/base-response.php');

    // Include libraries and enums
    Base_Functions::autoload_modules(LIBRARIES_PATH . '/', 'base-', '.php', $exclude);
    Base_Functions::autoload_modules($_SERVER["DOCUMENT_ROOT"] . '/enums/', '', '.php');