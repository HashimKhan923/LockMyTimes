<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Main\Tenant;
use Illuminate\Http\Request;

/**
 * The "Login" entry point on the marketing site (lockmytimes.com). Neither the
 * employee portal nor the admin panel exist at the root domain — every real login
 * lives under /t/{tenant}/... — so this exists purely to get a visitor to the
 * right place: if we remember which company they last visited (a cookie set by
 * the tenant layouts, see layouts.admin / layouts.employee), send them straight
 * there and let that portal's own auth middleware decide "still logged in" vs
 * "show the login form" — it already does that correctly on its own. If we don't
 * know their company yet, ask for it once.
 */
class LoginRedirectController extends Controller
{
    private const COOKIE = 'lmt_last_tenant';

    public function employee(Request $request)
    {
        return $this->redirectOrPrompt($request, 'portal', 'employee');
    }

    public function admin(Request $request)
    {
        return $this->redirectOrPrompt($request, 'admin', 'admin');
    }

    public function resolve(Request $request)
    {
        $request->validate(['slug' => 'required|string', 'type' => 'required|in:admin,portal']);

        $tenant = Tenant::where('slug', $request->slug)
            ->orWhere('subdomain', $request->slug)
            ->first();

        if (! $tenant) {
            return back()->withInput()->with('error', "We couldn't find a company at that address. Double-check it with your HR admin.");
        }

        $cookie = cookie(self::COOKIE, $tenant->slug, 60 * 24 * 365, null, null, false, false);

        return redirect("/t/{$tenant->slug}/{$request->type}")->withCookie($cookie);
    }

    private function redirectOrPrompt(Request $request, string $segment, string $type)
    {
        $slug = $request->cookie(self::COOKIE);

        if ($slug && Tenant::where('slug', $slug)->exists()) {
            return redirect("/t/{$slug}/{$segment}");
        }

        return view('public.login-prompt', ['type' => $type]);
    }
}
