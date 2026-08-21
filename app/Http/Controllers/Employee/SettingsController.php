<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /* ================================================================
     | INDEX
     |================================================================*/
    public function index(string $tenant)
    {
        $user = auth()->user();
        $emp  = $user->employee;
        abort_unless($emp, 403, 'No employee profile linked.');

        return view('employee.settings.index', [
            'tenantSlug' => $tenant,
            'emp'        => $emp,
            'user'       => $user,
        ]);
    }

    /* ================================================================
     | UPDATE — Preferences
     |================================================================*/
    public function update(string $tenant, Request $request)
    {
        $user = auth()->user();

        // timezone is deliberately not accepted here — it's admin-managed only (set from
        // Admin > Employees), never an employee self-service preference.
        $validated = $request->validate([
            'locale'      => ['required', Rule::in(array_keys(self::locales()))],
            'date_format' => ['required', Rule::in(['DMY', 'MDY', 'YMD'])],
            'time_format' => ['required', Rule::in(['12', '24'])],
            'theme'       => ['required', Rule::in(['light', 'dark', 'system'])],
        ]);

        $user->update($validated);

        return back()->with('success', 'Preferences saved successfully.')->with('theme_saved', true);
    }

    /* ================================================================
     | UPDATE — Notifications
     |================================================================*/
    public function updateNotifications(string $tenant, Request $request)
    {
        $user = auth()->user();
        $keys = array_keys(\App\Models\Tenant\User::defaultNotificationPreferences());

        $prefs = [];
        foreach ($keys as $key) {
            $prefs[$key] = [
                'in_app' => (bool) $request->boolean("notif_{$key}_in_app"),
                'email'  => (bool) $request->boolean("notif_{$key}_email"),
            ];
        }

        $user->update(['notification_preferences' => $prefs]);

        return back()->with('success', 'Notification preferences saved.')->withFragment('notifications');
    }

    /* ================================================================
     | UPDATE — Privacy
     |================================================================*/
    public function updatePrivacy(string $tenant, Request $request)
    {
        $emp = auth()->user()->employee;
        abort_unless($emp, 403);

        $keys = array_keys(Employee::defaultPrivacySettings());
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = (bool) $request->boolean($key);
        }

        $emp->update(['privacy_settings' => $settings]);

        return back()->with('success', 'Privacy settings saved.')->withFragment('privacy');
    }

    /* ================================================================
     | SIGN OUT OTHER SESSIONS
     |================================================================*/
    public function signOutOtherSessions(string $tenant, Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        // Flush all other sessions for this user from the sessions table
        DB::connection('tenant')
            ->table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', session()->getId())
            ->delete();

        return back()->with('success', 'All other sessions have been signed out.')->withFragment('security');
    }

    /* ================================================================
     | HELPERS
     |================================================================*/
    public static function locales(): array
    {
        return [
            'en' => 'English',
            'es' => 'Español',
            'fr' => 'Français',
            'ar' => 'العربية',
        ];
    }
}
