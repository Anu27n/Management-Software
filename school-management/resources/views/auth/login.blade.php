<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .login-card {
            background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            padding: 40px; width: 100%; max-width: 420px;
        }
        .login-card .brand { text-align: center; margin-bottom: 30px; }
        .login-card .brand i { font-size: 3rem; color: #667eea; }
        .login-card .brand h4 { margin-top: 10px; font-weight: 700; color: #1e293b; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25); }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2); border: none;
            padding: 12px; font-weight: 600; border-radius: 8px;
        }
        .btn-primary:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <i class="bi bi-mortarboard-fill"></i>
            <h4>School Management System</h4>
            <p class="text-muted">Sign in to your account</p>
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
            <small class="text-muted">Default: admin@school.com / password</small>
        </div>
    </div>
</body>
</html>
