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

function generateBase64AppKey(): ?string
{
    try {
        return 'base64:' . base64_encode(random_bytes(32));
    } catch (Throwable $e) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes(32, $strong);
            if ($bytes !== false && strlen($bytes) === 32) {
                return 'base64:' . base64_encode($bytes);
            }
        }
    }

    return null;
}

function ensureEnvAppKey(string $envFile): bool
{
    if (!file_exists($envFile) || !is_readable($envFile) || !is_writable($envFile)) {
        return false;
    }

    $contents = file_get_contents($envFile);
    if ($contents === false) {
        return false;
    }

    if (preg_match('/^APP_KEY\s*=\s*(.+)\s*$/m', $contents, $matches) && trim((string) $matches[1]) !== '') {
        return true;
    }

    $key = generateBase64AppKey();
    if ($key === null) {
        return false;
    }

    if (preg_match('/^APP_KEY\s*=.*$/m', $contents)) {
        $updated = preg_replace('/^APP_KEY\s*=.*$/m', 'APP_KEY=' . $key, $contents, 1);
    } else {
        $updated = rtrim($contents) . "\nAPP_KEY=" . $key . "\n";
    }

    return $updated !== null && file_put_contents($envFile, $updated) !== false;
}

// Clear config cache when .env is newer (installer wrote it) or when app code was just updated (new deploy)
$baseDir = dirname(__DIR__);
$configCache = $baseDir . '/bootstrap/cache/config.php';
$envFile = $baseDir . '/.env';
$installedFile = $baseDir . '/.installed';

// Fresh uploads on shared hosting should go to the installer before Laravel boots.
if (!file_exists($installedFile) && file_exists(__DIR__ . '/installer.php')) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (!preg_match('~/((installer|clear-config-cache|run-migrate)\.php)?$~i', $requestPath)) {
        header('Location: installer.php');
        exit;
    }
}

if (file_exists($installedFile)) {
    if (!file_exists($envFile)) {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Installation incomplete</title>';
        echo '<style>body{font-family:sans-serif;max-width:620px;margin:2rem auto;padding:1.5rem;background:#fff7ed;}h1{color:#9a3412;}p{color:#7c2d12;line-height:1.6;}code{background:#fed7aa;padding:2px 6px;border-radius:4px;}</style></head><body>';
        echo '<h1>Installation incomplete</h1><p>The application is marked installed, but the <code>.env</code> file is missing.</p>';
        echo '<p>Delete <code>.installed</code> from the Laravel root and run <code>installer.php</code> again.</p></body></html>';
        exit;
    }

    if (!ensureEnvAppKey($envFile)) {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>APP_KEY missing</title>';
        echo '<style>body{font-family:sans-serif;max-width:620px;margin:2rem auto;padding:1.5rem;background:#fff7ed;}h1{color:#9a3412;}p{color:#7c2d12;line-height:1.6;}code{background:#fed7aa;padding:2px 6px;border-radius:4px;}</style></head><body>';
        echo '<h1>APP_KEY missing</h1><p>The installer did not save a valid <code>APP_KEY</code> into <code>.env</code>, so Laravel cannot start.</p>';
        echo '<p>Re-upload the latest package and run <code>installer.php</code> again, or edit <code>.env</code> and add a valid <code>APP_KEY=base64:...</code> line.</p></body></html>';
        exit;
    }
}

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
