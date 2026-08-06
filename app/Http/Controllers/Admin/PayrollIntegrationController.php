<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PayrollProviderException;
use App\Http\Controllers\Controller;
use App\Models\Tenant\PayrollIntegration;
use App\Services\PayrollSyncService;
use Illuminate\Http\Request;

class PayrollIntegrationController extends Controller
{
    public function __construct(protected PayrollSyncService $sync)
    {
    }

    /**
     * Save (or update) the Payroll Relief API key and immediately test the connection.
     * There is at most one integration row per tenant per provider.
     */
    public function connect(string $tenant, Request $request)
    {
        $data = $request->validate([
            'api_key' => 'required|string|max:2000',
            'base_url' => 'nullable|url|max:255',
        ]);

        $integration = PayrollIntegration::firstOrNew(['provider' => 'payroll_relief']);
        $integration->fill([
            'api_key' => $data['api_key'],
            'base_url' => $data['base_url'] ?: null,
            'status' => 'disconnected',
            'last_error' => null,
        ])->save();

        try {
            $this->sync->testConnection($integration);
            $integration->update(['status' => 'connected', 'last_error' => null]);

            return back()->with('success', 'Connected to Payroll Relief.');
        } catch (PayrollProviderException $e) {
            $integration->update(['status' => 'error', 'last_error' => $e->getMessage()]);

            return back()->with('error', $e->getMessage());
        }
    }

    public function syncNow(string $tenant, Request $request)
    {
        $integration = PayrollIntegration::where('provider', 'payroll_relief')->first();

        if (! $integration || ! $integration->isConnected()) {
            return back()->with('error', 'Connect Payroll Relief before syncing.');
        }

        try {
            $result = $this->sync->sync($integration);

            return back()->with(
                'success',
                "Sync complete — {$result['employees']} employee(s), {$result['payslips']} new payslip(s) imported."
            );
        } catch (PayrollProviderException $e) {
            $integration->update(['status' => 'error', 'last_error' => $e->getMessage()]);

            return back()->with('error', $e->getMessage());
        }
    }

    public function disconnect(string $tenant, Request $request)
    {
        PayrollIntegration::where('provider', 'payroll_relief')->delete();

        return back()->with('success', 'Payroll Relief disconnected. LockMyTimes\'s own payroll system remains active.');
    }
}
