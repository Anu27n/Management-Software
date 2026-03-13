<?php
/**
 * School Management System - Web Installer
 * Place this file inside Laravel's public/ folder.
 * The installer will auto-detect Laravel root as ../
 */

session_start();

// Detect Laravel root automatically when installer is inside /public
$basePath = realpath(__DIR__ . '/../');

if ($basePath === false || !is_dir($basePath)) {
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Installer Error</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="alert alert-danger">
                <strong>Installer Error:</strong> Could not detect Laravel root directory from ../
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Prevent access after installation is complete
$lockFile = $basePath . '/.installed';

if (file_exists($lockFile)) {
    $installedUrl = discoverInstalledAppUrl($basePath);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Installer Locked</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        <style>
            body { background: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; }
            .card-wrap { max-width: 620px; margin: 60px auto; border: none; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
            .card-head { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; border-radius: 16px 16px 0 0; }
        </style>
    </head>
    <body>
        <div class="card card-wrap">
            <div class="card-body p-4 card-head">
                <h4 class="mb-1"><i class="bi bi-shield-lock me-2"></i>Installer Locked</h4>
                <p class="mb-0 opacity-75">This application is already installed.</p>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-success mb-3">
                    Installation lock file exists: <code>.installed</code>
                </div>
                <p class="mb-3">For security, reinstall is disabled by default. If you need a fresh install, delete the lock file and run again.</p>
                <div class="d-flex gap-2">
                    <a class="btn btn-primary" href="<?= htmlspecialchars($installedUrl, ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Open Application</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'fix_permissions') {
        tryFixPermissions($basePath);
        // Reload page so fresh check runs
        header('Location: installer.php?step=1&fixed=1');
        exit;
    } elseif ($action === 'check_requirements') {
        $step = 2;
    } elseif ($action === 'save_database') {
        $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
        $dbPort = trim($_POST['db_port'] ?? '3306');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';

        // Validate inputs
        if (empty($dbName)) $errors[] = 'Database name is required.';
        if (empty($dbUser)) $errors[] = 'Database username is required.';

        if (empty($errors)) {
            // Test database connection
            try {
                $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName}";
                $pdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
                $pdo = null;

                // Save to session for next step
                $_SESSION['db'] = [
                    'host' => $dbHost,
                    'port' => $dbPort,
                    'name' => $dbName,
                    'user' => $dbUser,
                    'pass' => $dbPass,
                ];
                $step = 3;
            } catch (PDOException $e) {
                $errors[] = 'Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
        } else {
            $step = 2;
        }
    } elseif ($action === 'save_admin') {
        $adminName = trim($_POST['admin_name'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPass = $_POST['admin_pass'] ?? '';
        $appUrl = rtrim(trim($_POST['app_url'] ?? ''), '/');

        if (empty($adminName)) $errors[] = 'Admin name is required.';
        if (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid admin email is required.';
        if (strlen($adminPass) < 6) $errors[] = 'Admin password must be at least 6 characters.';
        if (empty($appUrl)) $errors[] = 'Application URL is required.';

        if (empty($errors)) {
            $_SESSION['admin'] = [
                'name' => $adminName,
                'email' => $adminEmail,
                'pass' => $adminPass,
                'url' => $appUrl,
            ];
            $step = 4;
        } else {
            $step = 3;
        }
    } elseif ($action === 'install') {
        $db = $_SESSION['db'] ?? null;
        $admin = $_SESSION['admin'] ?? null;

        if (!$db || !$admin) {
            $errors[] = 'Session expired. Please start over.';
            $step = 1;
        } else {
            ensureRuntimeDirectories($basePath);

            // Step 1: Write .env file
            try {
                $appKey = 'base64:' . base64_encode(random_bytes(32));
            } catch (Throwable $e) {
                $errors[] = 'Could not generate APP_KEY: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                $appKey = '';
            }

            $envContent = "APP_NAME=\"School Management System\"\n";
            $envContent .= "APP_ENV=production\n";
            $envContent .= "APP_KEY={$appKey}\n";
            $envContent .= "APP_DEBUG=false\n";
            $envContent .= "APP_URL=" . $admin['url'] . "\n\n";
            $envContent .= "APP_LOCALE=en\nAPP_FALLBACK_LOCALE=en\nAPP_FAKER_LOCALE=en_US\n\n";
            $envContent .= "LOG_CHANNEL=stack\nLOG_STACK=single\nLOG_LEVEL=error\n\n";
            $envContent .= "DB_CONNECTION=mysql\n";
            $envContent .= "DB_HOST=" . $db['host'] . "\n";
            $envContent .= "DB_PORT=" . $db['port'] . "\n";
            $envContent .= "DB_DATABASE=" . $db['name'] . "\n";
            $envContent .= "DB_USERNAME=" . $db['user'] . "\n";
            $envContent .= "DB_PASSWORD=" . $db['pass'] . "\n\n";
            $envContent .= "SESSION_DRIVER=database\n";
            $envContent .= "CACHE_STORE=database\n\n";
            $envContent .= "MAIL_MAILER=log\n";

            if (file_put_contents($basePath . '/.env', $envContent) === false) {
                $errors[] = 'Could not write .env file. Check folder permissions.';
            }

            if (empty($errors)) {
                // Step 2: Run artisan commands
                // Clear caches
                $configClearRet = runArtisanCommand($basePath, 'config:clear', $out1);
                if ($configClearRet !== 0) {
                    $errors[] = 'config:clear failed: ' . htmlspecialchars(implode("\n", $out1), ENT_QUOTES, 'UTF-8');
                }

                $cacheClearRet = runArtisanCommand($basePath, 'cache:clear', $out2);
                if ($cacheClearRet !== 0) {
                    $errors[] = 'cache:clear failed: ' . htmlspecialchars(implode("\n", $out2), ENT_QUOTES, 'UTF-8');
                }

                // Run migrations
                $migRet = runArtisanCommand($basePath, 'migrate --force', $migrationOutput);
                if ($migRet !== 0) {
                    $errors[] = 'Migration failed: ' . htmlspecialchars(implode("\n", $migrationOutput), ENT_QUOTES, 'UTF-8');
                }
            }

            if (empty($errors)) {
                // Step 3: Create admin user via database directly
                try {
                    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']}";
                    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    ]);

                    $hashedPass = password_hash($admin['pass'], PASSWORD_BCRYPT, ['cost' => 12]);
                    $now = date('Y-m-d H:i:s');

                    $checkAdminStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                    $checkAdminStmt->execute([$admin['email']]);
                    $existingAdminId = $checkAdminStmt->fetchColumn();

                    if (!$existingAdminId) {
                        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_active, created_at, updated_at) VALUES (?, ?, ?, 'admin', 1, ?, ?)");
                        $stmt->execute([$admin['name'], $admin['email'], $hashedPass, $now, $now]);
                    }

                    // Create default academic year
                    $year = date('Y');
                    $nextYear = $year + 1;

                    $checkYearStmt = $pdo->prepare("SELECT id FROM academic_years WHERE is_active = 1 LIMIT 1");
                    $checkYearStmt->execute();
                    $activeYearId = $checkYearStmt->fetchColumn();

                    if (!$activeYearId) {
                        $stmt2 = $pdo->prepare("INSERT INTO academic_years (name, start_date, end_date, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, ?, ?)");
                        $stmt2->execute(["{$year}-{$nextYear}", "{$year}-04-01", "{$nextYear}-03-31", $now, $now]);
                    }

                    $pdo = null;
                } catch (PDOException $e) {
                    $errors[] = 'Admin user creation failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                }
            }

            if (empty($errors)) {
                // Step 4: Set permissions
                @chmod($basePath . '/storage', 0775);
                @chmod($basePath . '/bootstrap/cache', 0775);
                $dirs = ['storage/app', 'storage/framework', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'];
                foreach ($dirs as $dir) {
                    $fullPath = $basePath . '/' . $dir;
                    if (!is_dir($fullPath)) @mkdir($fullPath, 0775, true);
                    @chmod($fullPath, 0775);
                }

                // Create public storage symlink if possible
                $storageLinkOutput = [];
                $storageLinkRet = runArtisanCommand($basePath, 'storage:link --relative', $storageLinkOutput);
                if ($storageLinkRet !== 0) {
                    $storageLinkRet = runArtisanCommand($basePath, 'storage:link', $storageLinkOutput);
                }

                // Optimize for production
                runArtisanCommand($basePath, 'config:cache', $configCacheOutput);
                runArtisanCommand($basePath, 'route:cache', $routeCacheOutput);
                runArtisanCommand($basePath, 'view:cache', $viewCacheOutput);

                // Write lock file
                file_put_contents($lockFile, date('Y-m-d H:i:s') . ' - Installed');

                $_SESSION['installed_url'] = $admin['url'];

                // Clear session
                $step = 5;
                $success = 'Installation completed successfully!';
            } else {
                $step = 4;
            }
        }
    }
}

// Auto-fix folder permissions (safe to run at any time)
function tryFixPermissions(string $basePath): bool {

    ensureRuntimeDirectories($basePath);

    // Directories that need to be writable
    $dirs = [
        $basePath . '/storage',
        $basePath . '/storage/app',
        $basePath . '/storage/app/public',
        $basePath . '/storage/framework',
        $basePath . '/storage/framework/cache',
        $basePath . '/storage/framework/sessions',
        $basePath . '/storage/framework/views',
        $basePath . '/storage/logs',
        $basePath . '/bootstrap/cache',
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @chmod($dir, 0775);
    }

    // .env: touch it if it doesn't exist so it becomes writable
    $envFile = $basePath . '/.env';
    if (!file_exists($envFile)) {
        @touch($envFile);
        @chmod($envFile, 0644);
    } else {
        @chmod($envFile, 0644);
    }

    return true;
}

function ensureRuntimeDirectories(string $basePath): void
{
    $requiredDirs = [
        $basePath . '/bootstrap/cache',
        $basePath . '/storage/framework/cache',
        $basePath . '/storage/framework/sessions',
        $basePath . '/storage/framework/views',
        $basePath . '/storage/logs',
    ];

    foreach ($requiredDirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @chmod($dir, 0775);
    }

    // Core writable directories for Laravel on shared hosting
    @chmod($basePath . '/storage', 0775);
    @chmod($basePath . '/bootstrap/cache', 0775);
}

function runArtisanCommand(string $basePath, string $command, array &$output = []): int
{
    $output = [];

    try {
        ensureRuntimeDirectories($basePath);

        if (!file_exists($basePath . '/vendor/autoload.php')) {
            $output[] = 'vendor/autoload.php not found at: ' . $basePath . '/vendor/autoload.php';
            return 1;
        }

        if (!file_exists($basePath . '/bootstrap/app.php')) {
            $output[] = 'bootstrap/app.php not found at: ' . $basePath . '/bootstrap/app.php';
            return 1;
        }

        static $kernels = [];

        if (!isset($kernels[$basePath])) {
            require_once $basePath . '/vendor/autoload.php';
            $app = require $basePath . '/bootstrap/app.php';
            $kernels[$basePath] = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        }

        $kernel = $kernels[$basePath];
        $status = (int) $kernel->call($command);
        $commandOutput = trim((string) $kernel->output());
        if ($commandOutput !== '') {
            $output = preg_split('/\r\n|\r|\n/', $commandOutput) ?: [];
        }

        return $status;
    } catch (\Throwable $e) {
        $output[] = 'Artisan command failed (' . $command . '): ' . $e->getMessage();
        return 1;
    }
}

function discoverInstalledAppUrl(string $basePath): string
{
    $envPath = $basePath . '/.env';
    if (is_file($envPath)) {
        $env = file_get_contents($envPath);
        if (is_string($env) && preg_match('/^APP_URL=(.*)$/m', $env, $matches)) {
            $appUrl = trim($matches[1], "\"' ");
            if ($appUrl !== '') {
                return $appUrl;
            }
        }
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) === '443');

    return ($isHttps ? 'https://' : 'http://') . $host;
}

// Check requirements
function checkRequirements(string $basePath): array {
    $checks = [];
    $checks['PHP Version >= 8.1'] = version_compare(PHP_VERSION, '8.1.0', '>=');
    $checks['PDO Extension'] = extension_loaded('pdo');
    $checks['PDO MySQL'] = extension_loaded('pdo_mysql');
    $checks['Mbstring Extension'] = extension_loaded('mbstring');
    $checks['OpenSSL Extension'] = extension_loaded('openssl');
    $checks['Tokenizer Extension'] = extension_loaded('tokenizer');
    $checks['JSON Extension'] = extension_loaded('json');
    $checks['cURL Extension'] = extension_loaded('curl');
    $checks['Fileinfo Extension'] = extension_loaded('fileinfo');
    $checks['GD Extension'] = extension_loaded('gd');
    $checks['Zip Extension'] = extension_loaded('zip');
    $checks['Vendor Autoload Present'] = file_exists($basePath . '/vendor/autoload.php');

    // Always try to auto-fix first before checking
    tryFixPermissions($basePath);

    $checks['storage/ Writable'] = is_writable($basePath . '/storage');
    $checks['bootstrap/cache/ Writable'] = is_writable($basePath . '/bootstrap/cache');
    // .env check: either the directory is writable (can create .env) or the file itself is writable
    $checks['.env Writable'] = is_writable($basePath . '/.env') || (!file_exists($basePath . '/.env') && is_writable($basePath));

    return $checks;
}

$allPassed = true;
if ($step === 1 || $step === 2) {
    $requirements = checkRequirements($basePath);
    foreach ($requirements as $passed) {
        if (!$passed) { $allPassed = false; break; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; min-height: 100vh; }
        .installer-card { max-width: 680px; margin: 40px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); overflow: hidden; }
        .installer-header { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; padding: 32px; text-align: center; }
        .installer-header h2 { font-weight: 700; margin-bottom: 4px; }
        .installer-header p { opacity: 0.85; margin: 0; }
        .installer-body { padding: 32px; }
        .step-indicator { display: flex; justify-content: center; gap: 8px; margin-bottom: 28px; }
        .step-dot { width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.85rem; color: #94a3b8; }
        .step-dot.active { background: #3b82f6; color: #fff; }
        .step-dot.done { background: #22c55e; color: #fff; }
        .check-item { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .check-item:last-child { border-bottom: none; }
        .check-icon { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; }
        .check-pass { background: #dcfce7; color: #16a34a; }
        .check-fail { background: #fee2e2; color: #dc2626; }
        .form-control, .form-select { border-radius: 8px; padding: 10px 14px; }
        .btn-install { background: linear-gradient(135deg, #3b82f6, #2563eb); border: none; padding: 12px 32px; border-radius: 10px; font-weight: 600; color: #fff; }
        .btn-install:hover { opacity: 0.9; color: #fff; }
        .success-box { text-align: center; padding: 20px; }
        .success-box .icon { font-size: 4rem; color: #22c55e; }
    </style>
</head>
<body>
<div class="installer-card">
    <div class="installer-header">
        <h2><i class="bi bi-mortarboard-fill me-2"></i>School Management System</h2>
        <p>Installation Wizard</p>
    </div>
    <div class="installer-body">
        <div class="step-indicator">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <div class="step-dot <?= $i < $step ? 'done' : ($i === $step ? 'active' : '') ?>">
                <?= $i < $step ? '&#10003;' : $i ?>
            </div>
            <?php endfor; ?>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $err): ?>
            <div><?= $err ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
        <h5 class="fw-bold mb-3">Step 1: System Requirements</h5>
        <p class="text-muted mb-3">Checking if your server meets the requirements...</p>

        <?php if (isset($_GET['fixed'])): ?>
        <div class="alert alert-info py-2"><i class="bi bi-arrow-repeat me-1"></i> Permission fix attempted. See results below.</div>
        <?php endif; ?>

        <?php foreach ($requirements as $name => $passed): ?>
        <div class="check-item">
            <div class="check-icon <?= $passed ? 'check-pass' : 'check-fail' ?>">
                <i class="bi <?= $passed ? 'bi-check-lg' : 'bi-x-lg' ?>"></i>
            </div>
            <span><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="ms-auto small <?= $passed ? 'text-success' : 'text-danger' ?>"><?= $passed ? 'OK' : 'FAIL' ?></span>
        </div>
        <?php endforeach; ?>

        <?php
        $permFailed = !($requirements['storage/ Writable'] ?? true)
                   || !($requirements['bootstrap/cache/ Writable'] ?? true)
                   || !($requirements['.env Writable'] ?? true);
        ?>

        <?php if ($permFailed): ?>
        <div class="alert alert-warning mt-4" style="border-radius:10px;">
            <strong><i class="bi bi-folder-x me-1"></i> Folder Permission Issue</strong>
            <p class="mb-2 mt-1 small">The installer tried to fix permissions automatically but some folders are still not writable. This is common on Hostinger shared hosting.</p>
            <strong class="small">Fix it manually in Hostinger File Manager:</strong>
            <ol class="small mb-2 mt-1" style="padding-left:18px;">
                <li>In <strong>hPanel</strong> go to <strong>Files → File Manager</strong></li>
                <li>Navigate to your Laravel project root (one level above <code>public/</code>)</li>
                <li>Right-click the <code>storage</code> folder → <strong>Permissions (chmod)</strong></li>
                <li>Set to <strong>755</strong> and check <em>"Apply to subdirectories and files"</em> → Save</li>
                <li>Repeat for the <code>bootstrap/cache</code> folder → set to <strong>755</strong></li>
                <li>Come back here and click <strong>Try Again</strong></li>
            </ol>
            <form method="POST" class="d-inline me-2">
                <input type="hidden" name="action" value="fix_permissions">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-magic me-1"></i>Auto-Fix Permissions</button>
            </form>
            <a href="installer.php?step=1" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-repeat me-1"></i>Refresh Check</a>
        </div>
        <?php endif; ?>

        <?php if (!$allPassed && !$permFailed): ?>
        <div class="alert alert-danger mt-3">
            <strong><i class="bi bi-exclamation-triangle me-1"></i> Missing PHP Extensions</strong><br>
            <span class="small">Contact your hosting provider to enable the missing PHP extensions listed above. Most major hosts (Hostinger, GoDaddy, Bluehost) support all required extensions — you may need to switch to <strong>PHP 8.1+</strong> in your hosting panel.</span>
        </div>
        <?php endif; ?>

        <form method="POST" class="mt-4">
            <input type="hidden" name="action" value="check_requirements">
            <button type="submit" class="btn btn-install w-100" <?= !$allPassed ? 'disabled' : '' ?>>
                <?= $allPassed ? 'Continue <i class="bi bi-arrow-right ms-1"></i>' : 'Fix Requirements to Continue' ?>
            </button>
        </form>

        <?php elseif ($step === 2): ?>
        <h5 class="fw-bold mb-3">Step 2: Database Configuration</h5>
        <p class="text-muted mb-3">Create a MySQL database on your hosting panel first, then enter the details below.</p>

        <form method="POST">
            <input type="hidden" name="action" value="save_database">
            <div class="row mb-3">
                <div class="col-8">
                    <label class="form-label fw-semibold">Database Host</label>
                    <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="col-4">
                    <label class="form-label fw-semibold">Port</label>
                    <input type="text" name="db_port" class="form-control" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Database Name</label>
                <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($_POST['db_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="school_management" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Database Username</label>
                <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($_POST['db_user'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Database Password</label>
                <input type="password" name="db_pass" class="form-control" value="">
            </div>
            <button type="submit" class="btn btn-install w-100">Test Connection & Continue <i class="bi bi-arrow-right ms-1"></i></button>
        </form>

        <?php elseif ($step === 3): ?>
        <h5 class="fw-bold mb-3">Step 3: Admin Account & App URL</h5>
        <p class="text-muted mb-3">Set up the administrator account and your website URL.</p>

        <form method="POST">
            <input type="hidden" name="action" value="save_admin">
            <div class="mb-3">
                <label class="form-label fw-semibold">Application URL</label>
                <input type="url" name="app_url" class="form-control" value="<?= htmlspecialchars($_POST['app_url'] ?? ('https://' . ($_SERVER['HTTP_HOST'] ?? 'yourdomain.com')), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://yourdomain.com" required>
                <div class="form-text">Your website URL without trailing slash</div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Admin Name</label>
                <input type="text" name="admin_name" class="form-control" value="<?= htmlspecialchars($_POST['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Admin Email</label>
                <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($_POST['admin_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="admin@yourschool.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Admin Password</label>
                <input type="password" name="admin_pass" class="form-control" minlength="6" required>
                <div class="form-text">Minimum 6 characters</div>
            </div>
            <button type="submit" class="btn btn-install w-100">Continue <i class="bi bi-arrow-right ms-1"></i></button>
        </form>

        <?php elseif ($step === 4): ?>
        <h5 class="fw-bold mb-3">Step 4: Install</h5>
        <p class="text-muted mb-3">Everything is ready. Click the button below to install the School Management System.</p>

        <div class="bg-light rounded p-3 mb-3">
            <div class="row small">
                <div class="col-6"><strong>Database:</strong> <?= htmlspecialchars($_SESSION['db']['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-6"><strong>Host:</strong> <?= htmlspecialchars($_SESSION['db']['host'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-6 mt-1"><strong>Admin:</strong> <?= htmlspecialchars($_SESSION['admin']['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-6 mt-1"><strong>URL:</strong> <?= htmlspecialchars($_SESSION['admin']['url'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <form method="POST" id="installForm">
            <input type="hidden" name="action" value="install">
            <button type="submit" class="btn btn-install w-100" id="installBtn" onclick="this.disabled=true;this.innerHTML='<span class=\'spinner-border spinner-border-sm me-2\'></span>Installing... Please wait';this.form.submit();">
                <i class="bi bi-download me-1"></i> Install Now
            </button>
        </form>

        <?php elseif ($step === 5): ?>
        <div class="success-box">
            <div class="icon"><i class="bi bi-check-circle-fill"></i></div>
            <h4 class="fw-bold mt-3">Installation Complete!</h4>
            <p class="text-muted">Your School Management System is ready to use.</p>

            <div class="alert alert-warning text-start mt-3">
                <strong><i class="bi bi-shield-exclamation me-1"></i> Security:</strong> Delete <code>installer.php</code> from your server immediately!
            </div>

            <a href="<?= htmlspecialchars($_SESSION['installed_url'] ?? $_SESSION['admin']['url'] ?? discoverInstalledAppUrl($basePath), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-install mt-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Go to Login
            </a>
            <?php unset($_SESSION['db'], $_SESSION['admin'], $_SESSION['installed_url']); ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
