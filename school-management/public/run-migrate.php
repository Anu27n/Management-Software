<?php
/**
 * One-time migration runner for use when installer migrations failed (e.g. mbstring was disabled).
 * Open: https://yourdomain.com/run-migrate.php?t=TOKEN
 * Token is shown by the installer when migration fails. DELETE this file after use.
 */
header('Content-Type: text/html; charset=utf-8');

$basePath = dirname(__DIR__);
$tokenFile = $basePath . '/storage/app/install_migrate_token.txt';

$token = $_GET['t'] ?? '';
if ($token === '' || !is_file($tokenFile) || trim(file_get_contents($tokenFile)) !== $token) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>Not found</title></head><body><p>Invalid or expired token.</p></body></html>';
    exit;
}

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Run migrations</title>';
echo '<style>body{font-family:sans-serif;max-width:600px;margin:40px auto;padding:20px;} .ok{color:green;} .err{color:red;} pre{background:#f5f5f5;padding:12px;overflow:auto;}</style></head><body>';

try {
    require $basePath . '/vendor/autoload.php';
    $app = require $basePath . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $status = (int) $kernel->call('migrate', ['--force' => true]);
    $output = trim((string) $kernel->output());

    @unlink($tokenFile);

    if ($status === 0) {
        echo '<p class="ok"><strong>Migrations completed successfully.</strong></p>';
        echo '<p>Your database tables are now created. You can log in to the application.</p>';
        if ($output !== '') {
            echo '<pre>' . htmlspecialchars($output, ENT_QUOTES, 'UTF-8') . '</pre>';
        }
    } else {
        echo '<p class="err"><strong>Migration failed.</strong></p>';
        echo '<pre>' . htmlspecialchars($output ?: 'No output', ENT_QUOTES, 'UTF-8') . '</pre>';
        echo '<p>Check storage/logs/laravel.log on the server. Token was not consumed; you can try again after fixing the error.</p>';
    }
} catch (Throwable $e) {
    echo '<p class="err"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p>Ensure the <strong>mbstring</strong> PHP extension is enabled in your hosting PHP settings, then try again.</p>';
}

echo '<p><strong>Security:</strong> Delete this file (run-migrate.php) from your server now.</p>';
echo '</body></html>';
