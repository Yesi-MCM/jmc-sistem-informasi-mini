<?php

// Serve static assets directly if they exist
if (is_file(__DIR__ . '/../public' . $_SERVER['REQUEST_URI'])) {
    return false;
}

// Bootstrap Laravel
require __DIR__ . '/../public/index.php';
