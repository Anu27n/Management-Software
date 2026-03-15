<?php

// Require mbstring before booting Laravel (avoids 500 from Excel/Str::studly)
if (!extension_loaded('mbstring')) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>PHP extension required</title>';
    echo '<style>body{font-family:sans-serif;max-width:560px;margin:2rem auto;padding:1.5rem;background:#f8fafc;}h1{color:#1e293b;}p{color:#475569;line-height:1.6;}code{background:#e2e8f0;padding:2px 6px;border-radius:4px;}ul{margin:1rem 0;padding-left:1.5rem;}</style></head><body>';
    echo '<h1>Server configuration required</h1>';
    echo '<p>The <strong>mbstring</strong> PHP extension must be enabled.</p>';
    echo '<p><strong>Hostinger:</strong></p><ul><li>hPanel → <strong>Advanced</strong> → <strong>PHP Configuration</strong></li><li>Enable <strong>mbstring</strong> and <strong>zip</strong></li><li>Save, then refresh this page</li></ul>';
    echo '<p><strong>cPanel:</strong> MultiPHP INI Editor → select your PHP version → enable <strong>mbstring</strong> and <strong>zip</strong>.</p>';
    echo '</body></html>';
    exit;
}

// Clear config cache when .env is newer (installer wrote it) or when app code was just updated (new deploy)
$baseDir = dirname(__DIR__);
$configCache = $baseDir . '/bootstrap/cache/config.php';
$envFile = $baseDir . '/.env';
if (file_exists($configCache)) {
    $cacheTime = filemtime($configCache);
    $envNewer = file_exists($envFile) && filemtime($envFile) > $cacheTime;
    $codeNewer = filemtime(__FILE__) > $cacheTime;
    if ($envNewer || $codeNewer) {
        @unlink($configCache);
    }
}

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
