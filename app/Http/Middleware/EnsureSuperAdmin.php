<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('superadmin')->check()) {
            return redirect()->route('superadmin.login');
        }

        $user = Auth::guard('superadmin')->user();

        if (! $user->is_active) {
            Auth::guard('superadmin')->logout();
            return redirect()->route('superadmin.login')
                ->with('error', 'Your account has been deactivated.');
        }

        return $next($request);
    }
}