<?php

// Check if the function header exists
if (!function_exists('header'))
    return;

// Remove the X-Powered-By header
header_remove('X-Powered-By');

// Set the headers
header('Feature-Policy: autoplay \'none\'; camera \'none\'');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('X-Permitted-Cross-Domain-Policies: none');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()');

// Content-Security-Policy header
// header("Content-Security-Policy: default-src 'self' 'unsafe-eval'; "
//     . "connect-src 'self' *.doubleclick.net *.google-analytics.com *.juicer.io *.googleapis.com *.facebook.com; "
//     . "frame-src 'self' *.youtube.com *.google.com; "
//     . "img-src 'self' *.googletagmanager.com *.google-analytics.com *.clatity.ms *.picsum.photos picsum.photos marketing.acerbis.it *.juicer.io *.gstatic.com *.google.com *.googleapis.com *.facebook.com data:; "
//     . "font-src 'self' *.juicer.io *.gstatic.com; "
//     . "script-src-elem 'self' 'unsafe-inline' cookiehub.net *.google-analytics.com *.clarity.ms *.googletagmanager.com *.juicer.io *.google.com *.googleapis.com *.facebook.net; "
//     . "style-src-elem 'self' 'unsafe-inline' cookiehub.net *.juicer.io *.googleapis.com *.google.com; "
//     . "style-src 'self' 'unsafe-inline' cookiehub.net *.juicer.io *.googleapis.com;");