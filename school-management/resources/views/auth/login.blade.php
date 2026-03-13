<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <title>Login - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { -webkit-tap-highlight-color: transparent; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            padding: 16px;
        }
        .login-card {
            background: #fff; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            padding: 40px 32px; width: 100%; max-width: 420px;
        }
        .login-card .brand { text-align: center; margin-bottom: 30px; }
        .login-card .brand .icon-circle {
            width: 72px; height: 72px; border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 16px;
        }
        .login-card .brand .icon-circle i { font-size: 2rem; color: #fff; }
        .login-card .brand h4 { font-weight: 700; color: #1e293b; margin-bottom: 4px; }
        .login-card .brand p { color: #94a3b8; font-size: 0.9rem; }
        .form-control {
            padding: 14px 16px; border-radius: 12px; font-size: 1rem;
            border: 1.5px solid #e2e8f0; min-height: 50px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.15);
        }
        .input-group-text {
            border-radius: 12px 0 0 12px; border: 1.5px solid #e2e8f0;
            border-right: none; background: #f8fafc; min-height: 50px;
        }
        .input-group .form-control { border-radius: 0 12px 12px 0; }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2); border: none;
            padding: 14px; font-weight: 600; border-radius: 12px; font-size: 1.05rem;
            min-height: 50px;
        }
        .btn-primary:hover { opacity: 0.9; }
        .btn-primary:active { transform: scale(0.98); }
        .form-check-input:checked { background-color: #667eea; border-color: #667eea; }
        @media (max-width: 480px) {
            .login-card { padding: 32px 24px; border-radius: 24px; }
            body { align-items: flex-end; padding-bottom: 0; }
            .login-card { border-radius: 24px 24px 0 0; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <div class="icon-circle">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <h4>School Management</h4>
            <p>Sign in to your account</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="admin@school.com" required autofocus>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="remember" id="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <button type="submit" class="btn btn-primary w-100">Sign In</button>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted d-block">Default admin: admin@school.com / password</small>
            <small class="text-muted">Teacher/Parent/Student accounts are created by admin from Settings > User Accounts.</small>
        </div>
    </div>
</body>
</html>
