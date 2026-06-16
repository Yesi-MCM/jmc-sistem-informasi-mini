<?php

// Fix Vercel's SCRIPT_NAME.
// When Vercel routes "/(.*) → /api/index.php", PHP sets SCRIPT_NAME to
// "/api/index.php", causing Laravel to compute base URL as "/api" and strip
// that prefix from every route. e.g. request "/api/auth/captcha" becomes
// path "auth/captcha" which never matches registered route "api/auth/captcha".
// Setting SCRIPT_NAME to "/index.php" makes Laravel compute an empty base URL,
// so the full path "api/auth/captcha" is used for route matching.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF']    = '/index.php';

// Serve static assets from public/ directly if they exist (no Laravel needed)
$publicFile = __DIR__ . '/../public' . $_SERVER['REQUEST_URI'];
if (is_file($publicFile)) {
    return false;
}

// Bootstrap Laravel and handle the request
require __DIR__ . '/../public/index.php';
