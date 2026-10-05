<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StaffAuthController extends Controller
{
    public function showLogin(Request $r)
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->role) {
                return redirect()->route(
                    config('clinic.roles.' . $user->role . '.home')
                );
            }
        }

        return view('auth.staff-login');
    }

    public function login(Request $r)
    {
        $data = $r->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt([
            'username' => $data['username'],
            'password' => $data['password'],
        ])) {
            return back()
                ->withInput($r->only('username'))
                ->with('login_error', 'Username atau password salah');
        }

        $r->session()->regenerate();

        $user = Auth::user();

        if (!$user || !$user->role) {
            Auth::logout();

            return back()
                ->withInput($r->only('username'))
                ->with('login_error', 'Akun staff belum memiliki role');
        }

        return redirect()->route(
            config('clinic.roles.' . $user->role . '.home')
        );
    }

    public function logout(Request $r)
    {
        Auth::logout();

        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('staff.login');
    }

    public function password()
    {
        return view('staff.account.password');
    }

    public function updatePassword(Request $r)
    {
        $r->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|regex:/[A-Za-z]/|regex:/[0-9]/|confirmed|different:current_password',
        ], [
            'password.min' => 'Gunakan minimal 8 karakter, dengan huruf dan angka.',
            'password.regex' => 'Gunakan minimal 8 karakter, dengan huruf dan angka.',
            'password.confirmed' => 'Konfirmasi password tidak sama dengan password baru.',
            'password.different' => 'Password baru tidak boleh sama dengan password lama.',
        ]);

        $user = Auth::user();

        if (!$user || !Hash::check($r->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password lama salah.',
            ]);
        }

        User::query()
            ->whereKey($user->getAuthIdentifier())
            ->update([
                'password' => Hash::make($r->password),
            ]);

        return redirect()
            ->route('staff.password')
            ->with('password_changed', true);
    }
}