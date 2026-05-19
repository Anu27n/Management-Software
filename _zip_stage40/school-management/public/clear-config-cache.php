<?php
/**
 * One-time: clear stale Laravel config/route cache and rebuild runtime caches.
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
$cacheFiles = ['bootstrap/cache/config.php', 'bootstrap/cache/routes-v7.php'];
foreach ($cacheFiles as $f) {
    $path = $basePath . '/' . $f;
    if (file_exists($path) && @unlink($path)) {
        $cleared[] = $f;
    }
}

$optimizeResults = [];
try {
    require $basePath . '/vendor/autoload.php';
    $app = require $basePath . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

    foreach (['config:cache', 'route:cache', 'view:cache'] as $command) {
        $status = (int) $kernel->call($command);
        $optimizeResults[$command] = [
            'status' => $status,
            'output' => trim((string) $kernel->output()),
        ];
    }
} catch (Throwable $e) {
    $optimizeResults['error'] = [
        'status' => 1,
        'output' => $e->getMessage(),
    ];
}

header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cache refreshed</title>';
echo '<style>body{font-family:sans-serif;max-width:680px;margin:2rem auto;padding:1.5rem;background:#f8fafc;}h1{color:#166534;}code{background:#dcfce7;padding:2px 6px;}pre{background:#fff;padding:10px;border:1px solid #e5e7eb;white-space:pre-wrap;} .warn{color:#b45309;} .err{color:#b91c1c;}</style></head><body>';
echo '<h1>Cache refreshed</h1>';
if ($appKeyFixed) {
    echo '<p>Verified that <code>APP_KEY</code> exists in <code>.env</code>.</p>';
} else {
    echo '<p class="err"><strong>Warning:</strong> Could not confirm or repair <code>APP_KEY</code>. Check <code>.env</code> permissions if the site still shows 500.</p>';
}
if (!empty($cleared)) {
    echo '<p>Removed stale cache files: ' . htmlspecialchars(implode(', ', $cleared), ENT_QUOTES, 'UTF-8') . '.</p>';
} else {
    echo '<p>No stale config/route cache files were found.</p>';
}

if (!empty($optimizeResults)) {
    echo '<h2>Optimization</h2><ul>';
    foreach ($optimizeResults as $command => $result) {
        if ($command === 'error') {
            echo '<li class="err"><strong>Error:</strong> ' . htmlspecialchars($result['output'], ENT_QUOTES, 'UTF-8') . '</li>';
            continue;
        }

        $class = $result['status'] === 0 ? '' : ' class="warn"';
        $label = $result['status'] === 0 ? 'completed' : 'skipped/failed';
        echo '<li' . $class . '><strong>' . htmlspecialchars($command, ENT_QUOTES, 'UTF-8') . '</strong>: ' . $label . '</li>';
        if ($result['output'] !== '') {
            echo '<pre>' . htmlspecialchars($result['output'], ENT_QUOTES, 'UTF-8') . '</pre>';
        }
    }
    echo '</ul>';
}

echo '<p>Reload your site and test the speed again.</p>';
echo '<p><strong>Important:</strong> Delete this file (<code>clear-config-cache.php</code>) from your server for security.</p>';
echo '</body></html>';
