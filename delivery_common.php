<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
if (session_status()!==PHP_SESSION_ACTIVE) session_start();
function dc_uid(){ return (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0); }
function dc_role(){ return strtolower((string)($_SESSION['role'] ?? '')); }
function dc_back(){ return $_SERVER['HTTP_REFERER'] ?? url_path('index.php'); }
