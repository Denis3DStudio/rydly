<?php
// Enable GZIP compression if the client supports it
// if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
//     ob_start('ob_gzhandler');
// } else {
    ob_start();
// }

// Set cache control headers
header("Cache-Control: public");

// Set a default expiration period (10 days)
header("Expires: " . gmdate("D, d M Y H:i:s", time() + 864000) . " GMT"); 

// Get the file extension from the requested URL
$ext = pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION);

// Custom expiration handling based on the file extension
switch ($ext) {
    case 'css':
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + 604800) . " GMT"); // 1 week for CSS
        header("Cache-Control: max-age=604800, public");
        break;
    case 'js':
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + 604800) . " GMT"); // 1 week for JavaScript
        header("Cache-Control: max-age=604800, public");
        break;
    case 'gif':
    case 'png':
    case 'jpeg':
    case 'jpg':
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + 2592000) . " GMT"); // 1 month for images
        header("Cache-Control: max-age=2592000, public");
        break;
    case 'ico':
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + 31536000) . " GMT"); // 1 year for icons
        header("Cache-Control: max-age=31536000, public");
        break;
    case 'xml':
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + 2592000) . " GMT"); // 1 month for XML
        header("Cache-Control: max-age=2592000, public");
        break;
    default:
        // Default expiration for other files (10 days)
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + 864000) . " GMT"); 
        header("Cache-Control: max-age=864000, public");
        break;
}

// Handle old browser issues (simulated logic)
if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/^Mozilla\/4/', $_SERVER['HTTP_USER_AGENT']))
    header("Cache-Control: no-cache, must-revalidate");

if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/^Mozilla\/4\.0[678]/', $_SERVER['HTTP_USER_AGENT']))
    header("Cache-Control: no-gzip");

if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)
    header("Cache-Control: !no-gzip, !gzip-only-text/html");

// End the output buffer and send everything to the browser
ob_end_flush();