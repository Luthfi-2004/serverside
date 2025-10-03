<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        // Tampilkan Blade login (tanpa Livewire)
        return view('auth.login', ['title' => 'Login']);
    }

    public function login(Request $request)
    {
        // Validasi basic
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $remember = (bool) ($credentials['remember'] ?? false);
        unset($credentials['remember']);

        // Coba login dengan kolom 'username'
        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']], $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        // Fallback: kalau yang diinput email, coba pakai email
        if (filter_var($credentials['username'], FILTER_VALIDATE_EMAIL)) {
            if (Auth::attempt(['email' => $credentials['username'], 'password' => $credentials['password']], $remember)) {
                $request->session()->regenerate();
                return redirect()->intended(route('dashboard'));
            }
        }

        return back()
            ->withErrors(['username' => 'Username atau password salah.'])
            ->withInput($request->only('username', 'remember'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
