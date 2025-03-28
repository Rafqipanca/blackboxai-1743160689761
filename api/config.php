<?php
// Set header untuk mengizinkan CORS (untuk development saja)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting (matikan di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);