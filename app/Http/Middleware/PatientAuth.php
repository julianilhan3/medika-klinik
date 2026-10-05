<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PatientAuth
{
    public function handle(Request $request, Closure $next)
    {
        return $request->session()->has('patient') ? $next($request) : redirect()->route('portal.login');
    }
}
