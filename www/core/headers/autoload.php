<?php

try {

    // 301
    include_once("301.php");

    // Session
    include_once("session.php");
    
    // Security headers
    include_once("security.php");

    // Optimizations
    include_once("optimization.php");

} catch (\Throwable $th) {}