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
if (!empty($cleared)) {
    echo '<p>Removed: ' . htmlspecialchars(implode(', ', $cleared), ENT_QUOTES, 'UTF-8') . '.</p>';
} else {
    echo '<p>No cached config files were found (already clear).</p>';
}
echo '<p>Laravel will now use the database settings from your <code>.env</code> file. Reload your site and try again.</p>';
echo '<p><strong>Important:</strong> Delete this file (<code>clear-config-cache.php</code>) from your server for security.</p>';
echo '</body></html>';
