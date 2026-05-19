<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required',
        ]);

        $login = strtolower(trim((string) $validated['login']));
        $credentialField = filter_var($login, FILTER_VALIDATE_EMAIL) || !Schema::hasColumn('users', 'username')
            ? 'email'
            : 'username';
        $credentials = [
            $credentialField => $login,
            'password' => $validated['password'],
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if (!Auth::user()->is_active) {
                Auth::logout();
                return back()->withErrors(['login' => 'Your account has been deactivated.'])->onlyInput('login');
            }

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors(['login' => 'Invalid credentials.'])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
