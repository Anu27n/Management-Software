<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    @php
        $schoolName = $siteSettings->school_name ?: 'School Management System';
        $brandLogo = $siteSettings->logo_url ?: '/images/school-logo.png';
        $primary = $siteSettings->app_primary_color ?? '#0f6b56';
        $primaryDark = $siteSettings->app_primary_dark_color ?? '#0b5443';
        $background = $siteSettings->app_background_color ?? '#eef6f3';
        $sidebarBg = $siteSettings->app_sidebar_bg_color ?? '#031814';
    @endphp
    <meta name="theme-color" content="{{ $primary }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <title>Login - {{ $schoolName }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { -webkit-tap-highlight-color: transparent; }
        body {
            background:
                radial-gradient(circle at 18% 20%, color-mix(in srgb, {{ $primary }} 36%, transparent), transparent 40%),
                radial-gradient(circle at 82% 80%, color-mix(in srgb, {{ $primaryDark }} 36%, transparent), transparent 42%),
                linear-gradient(145deg, {{ $sidebarBg }} 0%, color-mix(in srgb, {{ $sidebarBg }} 70%, #000000) 55%, color-mix(in srgb, {{ $background }} 20%, #00110d) 100%);
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            padding: 16px;
        }
        .login-card {
            background: rgba(255,255,255,0.94);
            backdrop-filter: blur(10px);
            border-radius: 22px;
            border: 1px solid rgba(15, 107, 86, 0.22);
            box-shadow: 0 24px 64px rgba(1, 21, 17, 0.45);
            padding: 34px 30px;
            width: 100%;
            max-width: 420px;
        }
        .login-wrap {
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: calc(100dvh - 32px);
        }
        .login-card .brand { text-align: center; margin-bottom: 24px; }
        .login-card .brand .brand-logo {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 14px;
            border: 2px solid rgba(15, 107, 86, 0.35);
            box-shadow: 0 0 0 6px rgba(15, 107, 86, 0.08);
        }
        .login-card .brand h4 {
            font-weight: 800;
            color: #04342a;
            margin-bottom: 4px;
            letter-spacing: 0.2px;
        }
        .login-card .brand p { color: #356458; font-size: 0.9rem; margin-bottom: 0; }
        .form-control {
            padding: 13px 15px;
            border-radius: 12px;
            font-size: 0.98rem;
            border: 1.5px solid #e2e8f0; min-height: 50px;
        }
        .form-control:focus {
            border-color: {{ $primary }};
            box-shadow: 0 0 0 3px color-mix(in srgb, {{ $primary }} 22%, transparent);
        }
        .input-group-text {
            border-radius: 12px 0 0 12px; border: 1.5px solid #e2e8f0;
            border-right: none; background: #f8fafc; min-height: 50px;
        }
        .input-group .form-control { border-radius: 0 12px 12px 0; }
        .btn-primary {
            background: linear-gradient(135deg, {{ $primary }}, {{ $primaryDark }}); border: none;
            padding: 13px;
            font-weight: 700;
            border-radius: 12px;
            font-size: 1rem;
            min-height: 50px;
        }
        .btn-primary:hover { opacity: 0.9; }
        .btn-primary:active { transform: scale(0.98); }
        .form-check-input:checked { background-color: {{ $primary }}; border-color: {{ $primary }}; }
        .login-footer {
            text-align: center;
            color: #d5ece5;
            margin-top: 12px;
            font-size: 0.82rem;
            letter-spacing: 0.2px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding-bottom: max(2px, env(safe-area-inset-bottom));
        }
        .login-footer-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #1bd37d;
            box-shadow: 0 0 0 4px rgba(27, 211, 125, 0.2);
            flex: 0 0 auto;
        }
        @media (max-width: 480px) {
            body {
                align-items: center;
                justify-content: center;
                padding: 12px;
            }
            .login-wrap {
                max-width: 100%;
                min-height: calc(100dvh - 24px);
                justify-content: center;
            }
            .login-card {
                border-radius: 20px;
                max-width: 100%;
                padding: 26px 18px;
                margin: 0;
            }
            .login-card .brand {
                margin-bottom: 22px;
            }
            .login-card .brand .brand-logo {
                width: 74px;
                height: 74px;
                margin-bottom: 12px;
            }
            .login-card .brand h4 {
                font-size: 1.15rem;
            }
            .form-control,
            .input-group-text,
            .btn-primary {
                min-height: 48px;
            }
            .login-footer {
                margin: 10px 0 0;
                padding: 4px 0 max(6px, env(safe-area-inset-bottom));
                font-size: 0.78rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrap">
    <div class="login-card">
        <div class="brand">
            <img src="{{ $brandLogo }}" alt="School Logo" class="brand-logo">
            <h4>{{ $schoolName }}</h4>
            <p>Welcome back</p>
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
                <label class="form-label fw-semibold">Email or Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                    <input type="text" name="login" class="form-control" value="{{ old('login') }}" placeholder="Enter email or username" required autofocus>
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
            <small class="text-muted">Use your registered email or username to sign in.</small>
        </div>
    </div>
    <div class="login-footer"><span class="login-footer-dot" aria-hidden="true"></span>Powered by Styx Corp LLP</div>
    </div>
</body>
</html>
