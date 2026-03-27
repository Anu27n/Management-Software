<?php
/**
 * One-time: clear Laravel config cache so the app uses .env (fixes "connecting to wrong database").
 * Run once in browser, then DELETE this file from the server.
 */
$basePath = realpath(__DIR__ . '/../');
if ($basePath === false || !is_dir($basePath)) {
    http_response_code(500);
    echo 'Could not find Laravel root.';
    exit;
}
$envFile = $basePath . '/.env';

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

$appKeyFixed = ensureEnvAppKey($envFile);
$cleared = [];
$cacheFiles = ['bootstrap/cache/config.php', 'bootstrap/cache/routes-v7.php', 'bootstrap/cache/packages.php', 'bootstrap/cache/services.php'];
foreach ($cacheFiles as $f) {
    $path = $basePath . '/' . $f;
    if (file_exists($path) && @unlink($path)) {
        $cleared[] = $f;
    }
}
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Config cache cleared</title>';
echo '<style>body{font-family:sans-serif;max-width:520px;margin:2rem auto;padding:1.5rem;background:#f0fdf4;}h1{color:#166534;}code{background:#dcfce7;padding:2px 6px;}</style></head><body>';
echo '<h1>Config cache cleared</h1>';
if ($appKeyFixed) {
    echo '<p>Verified that <code>APP_KEY</code> exists in <code>.env</code>.</p>';
} else {
    echo '<p><strong>Warning:</strong> Could not confirm or repair <code>APP_KEY</code>. Check <code>.env</code> permissions if the site still shows 500.</p>';
}
if (!empty($cleared)) {
    echo '<p>Removed: ' . htmlspecialchars(implode(', ', $cleared), ENT_QUOTES, 'UTF-8') . '.</p>';
} else {
    echo '<p>No cached config files were found (already clear).</p>';
}
echo '<p>Laravel will now use the database settings from your <code>.env</code> file. Reload your site and try again.</p>';
echo '<p><strong>Important:</strong> Delete this file (<code>clear-config-cache.php</code>) from your server for security.</p>';
echo '</body></html>';
