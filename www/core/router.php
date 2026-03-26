<?php

    // Headers
    include_once("headers/autoload.php");

    // Check if default or API router
    $is_api_router = isset($_GET["is_api_router"]);

    // Set the request uri
    if($is_api_router) unset($_GET["is_api_router"]);
    
    // Check request uri
    if(isset($_GET["request_uri"]) && $_GET["request_uri"] != "") {
        $_SERVER["REQUEST_URI"] = $_GET["request_uri"];
        unset($_GET["request_uri"]);
    }

    // Explode url by . and get last path
    $exploded_url = explode(".", $_SERVER["REQUEST_URI"]);
    $last_url_path = end($exploded_url);

    // Check extension
    if($last_url_path != "" && in_array(strtoupper($last_url_path), ['CSS', 'DEB', 'EXE', 'GIF', 'ICO', 'JPEG', 'JPG', 'JS', 'JSON', 'MAP', 'MP3', 'MP4', 'P7M', 'PDF', 'PKG', 'PNG', 'PROPERTIES', 'SVG', 'TIF', 'TXT', 'WEBM', 'WEBMANIFEST', 'WEBP', 'WOFF', 'WOFF2'])) {
        http_response_code(404);
        exit;
    }

    unset($exploded_url);
    unset($last_url_path);

    // Set Rome timezone
    date_default_timezone_set("Europe/Rome");

    // Init base
    $base = "frontend";

    // Check $_GET
    if(isset($_GET["b"])) {
        $base = $_GET["b"];

        unset($_GET["b"]);

        // Remove from $_SERVER['QUERY_STRING'] the b
        $_SERVER['QUERY_STRING'] = str_replace("b=" . $base, "", $_SERVER['QUERY_STRING']);
    }

    // Check if private route
    if(strtoupper($base) == "PRIVATE") {
        http_response_code(404);
        exit;
    }

    // Check if base contains -bff
    $is_bff = strpos(strtoupper($base), "-BFF") !== false;

    // Remove -bff from base
    $base = str_replace("-bff", "", $base);

    // Set ACTIVE_PATH, ACTIVE_FULL_PATH and LIBRARIES_PATH
    DEFINE("ACTIVE_PATH", "/" . ltrim($base, "/"));
    DEFINE("ACTIVE_FULL_PATH", $_SERVER["DOCUMENT_ROOT"] . "/" . ltrim($base, "/"));
    DEFINE("LIBRARIES_PATH", $_SERVER["DOCUMENT_ROOT"] . "/core/libraries");

    // Include autoload libraries
    include_once(LIBRARIES_PATH . '/autoload.php');

    // Default router
    if(!$is_api_router) {

        // Check if BFF
        if($is_bff) {

            // Check if external overwrite url
            $external_overwriting = Request::GetHeader("External-Overwrite-Url");

            // Set default path
            $path = null;

            // Check if external overwrite url is set
            if(!Base_Functions::IsNullOrEmpty($external_overwriting)) {
                
                // Set request uri
                $_SERVER["REQUEST_URI"] = $external_overwriting;

                // Get index of -BFF
                $bff_index = strpos(strtoupper($external_overwriting), "-BFF");

                // Get path
                $path = ltrim(substr($external_overwriting, 0, $bff_index), "/");

            }

            // Init base router and calculate API
            $router = new Base_Router(true);
            $router->buildAPIEnums(true, $path);

            // Call
            (new Base_BFF($router->__API, $router->__API_requests))->Fire();

        }
        // Backend/Frontend
        else {

            // Check if path exists
            if(!file_exists(ACTIVE_FULL_PATH)) {
                http_response_code(404);
                exit;
            }

            // Render
            (new Base_Router())->Init();
        }
        

    }
    // Init base api
    else
        new Base_Api($base);