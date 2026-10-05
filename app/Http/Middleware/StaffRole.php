<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffRole
{
    public function handle(Request $request, Closure $next, string $role = 'any')
    {
        if (!Auth::check()) {
            return redirect()->route('staff.login');
        }

        $user = Auth::user();

        if (!$user->role) {
            Auth::logout();

            return redirect()
                ->route('staff.login')
                ->with('login_error', 'Akun staff belum memiliki role.');
        }

        if ($role !== 'any' && $user->role !== $role) {
            $home = config("clinic.roles.{$user->role}.home");

            if ($home) {
                return redirect()
                    ->route($home)
                    ->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
            }

            Auth::logout();

            return redirect()
                ->route('staff.login')
                ->with('login_error', 'Role akun tidak valid.');
        }

        return $next($request);
    }
}