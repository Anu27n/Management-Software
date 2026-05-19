<?php
/**
 * One-time optimizer for shared hosting.
 * Open it once in the browser after upload, then delete it.
 */
header('Content-Type: text/html; charset=utf-8');

$basePath = dirname(__DIR__);
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

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Optimize application</title>';
echo '<style>body{font-family:sans-serif;max-width:760px;margin:40px auto;padding:20px;} .ok{color:green;} .warn{color:#b45309;} .err{color:#b91c1c;} pre{background:#f5f5f5;padding:12px;overflow:auto;white-space:pre-wrap;}</style></head><body>';
echo '<h1>Optimize application</h1>';

if (!ensureEnvAppKey($envFile)) {
    echo '<p class="err"><strong>Error:</strong> APP_KEY is missing in .env and could not be repaired automatically.</p>';
    echo '<p>Fix the .env file permissions or re-run the installer, then try again.</p>';
    echo '</body></html>';
    exit;
}

try {
    @set_time_limit(0);
    @ini_set('memory_limit', '512M');

    foreach (['bootstrap/cache/config.php', 'bootstrap/cache/routes-v7.php'] as $cacheFile) {
        @unlink($basePath . '/' . $cacheFile);
    }

    require $basePath . '/vendor/autoload.php';
    $app = require $basePath . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

    $commands = [
        'config:cache' => 'Build cached configuration',
        'route:cache' => 'Build cached routes',
        'view:cache' => 'Compile Blade views',
    ];

    echo '<ul>';
    foreach ($commands as $command => $label) {
        $status = (int) $kernel->call($command);
        $output = trim((string) $kernel->output());

        if ($status === 0) {
            echo '<li class="ok"><strong>' . htmlspecialchars($command, ENT_QUOTES, 'UTF-8') . '</strong>: ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ' completed.</li>';
        } else {
            echo '<li class="warn"><strong>' . htmlspecialchars($command, ENT_QUOTES, 'UTF-8') . '</strong>: skipped or failed, but the app can still run.</li>';
        }

        if ($output !== '') {
            echo '<pre>' . htmlspecialchars($output, ENT_QUOTES, 'UTF-8') . '</pre>';
        }
    }
    echo '</ul>';

    echo '<p class="ok"><strong>Optimization finished.</strong> Reload the site and test the speed again.</p>';
} catch (Throwable $e) {
    echo '<p class="err"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
}

echo '<p><strong>Security:</strong> Delete <code>run-optimize.php</code> from the server after this finishes.</p>';
echo '</body></html>';
