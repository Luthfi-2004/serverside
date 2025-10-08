<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'usr'      => ['required','string'],
            'password' => ['required','string'],
        ], [], [
            'usr' => 'Username',
        ]);

        // Ambil user dari aicc-master.tb_user
        $user = User::where('usr', $credentials['usr'])->first();

        if (!$user || !$this->checkPassword($credentials['password'], (string) $user->pswd)) {
            return back()
                ->withErrors(['usr' => 'Username atau password salah.'])
                ->onlyInput('usr');
        }

        // Optional: blokir user tidak aktif
        if ($this->isInactive($user)) {
            return back()
                ->withErrors(['usr' => 'Akun tidak aktif.'])
                ->onlyInput('usr');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function isInactive($user): bool
    {
        // Sesuaikan logic kalau kolom is_active bukan boolean
        return isset($user->is_active) && (string)$user->is_active === '0';
    }

    /**
     * Verifikasi password fleksibel:
     * - bcrypt/argon2: Hash::check
     * - md5: bandingkan md5
     * - plain: bandingkan string biasa (tidak disarankan)
     */
    private function checkPassword(string $plain, string $stored): bool
    {
        if ($stored === '') {
            return false;
        }

        // bcrypt/argon2?
        if (Str::startsWith($stored, ['$2y$', '$argon2i$', '$argon2id$'])) {
            return Hash::check($plain, $stored);
        }

        // md5 32 hex?
        if (strlen($stored) === 32 && ctype_xdigit($stored)) {
            return hash_equals(strtolower($stored), md5($plain));
        }

        // fallback: plain text
        return hash_equals($stored, $plain);
    }
}
