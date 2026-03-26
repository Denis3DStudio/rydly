<?php

// Array of old URLs and their new destinations
$redirects = [
    '/old-page-to-redirect' => 'https://www.new-website.com/new-page'
];





// Get the current request URI
$request_uri = hash("sha256", "/" . ltrim(rtrim(strtoupper($_SERVER['REQUEST_URI']), "/"), "/") . "/");

// Format redirects keys
$keys = array_map(function ($item) { return hash("sha256", "/" . ltrim(rtrim(strtoupper($item), "/"), "/") . "/"); }, array_keys($redirects));

// Check if the current URI needs a redirect
if(in_array($request_uri, $keys)) {

    // Get the index of the request URI
    $index = array_search($request_uri, $keys);

    // Cast to simple array
    $redirects = array_values($redirects);

    // Get the new URL
    $new_url = $redirects[$index];

    // Redirect
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $new_url);
    exit;
}