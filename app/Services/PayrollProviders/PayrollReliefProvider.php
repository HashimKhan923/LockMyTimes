<?php

namespace App\Services\PayrollProviders;

use App\Exceptions\PayrollProviderException;
use App\Models\Tenant\PayrollIntegration;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Connector for AccountantsWorld's Payroll Relief Open API.
 *
 * AccountantsWorld does not publish public API documentation — access is granted per-partner
 * after contacting them directly (877-840-6122) to get onboarded. The endpoint paths, auth
 * scheme, and field names below are a best-effort implementation based on their public
 * announcement of the API's data categories (employer info, employee demographics, pay stubs,
 * tax/GL data). The `base_url` is admin-configurable specifically so it can be corrected without
 * a code change if the real onboarding docs specify something different.
 *
 * Once real credentials + docs are available: this is the only file that should need adjusting —
 * PayrollSyncService and the admin UI only ever see the normalized arrays this class returns.
 */
class PayrollReliefProvider implements PayrollProviderInterface
{
    private const DEFAULT_BASE_URL = 'https://api.accountantsworld.com/payrollrelief/v1';

    public function __construct(private PayrollIntegration $integration)
    {
    }

    public function testConnection(): void
    {
        $this->request('get', '/employer');
    }

    public function fetchEmployees(): array
    {
        $response = $this->request('get', '/employees');

        return collect($this->unwrapList($response))
            ->map(fn (array $e) => [
                'external_id' => (string) ($e['id'] ?? $e['employeeId'] ?? ''),
                'employee_code' => $e['employeeNumber'] ?? null,
                'first_name' => (string) ($e['firstName'] ?? ''),
                'last_name' => (string) ($e['lastName'] ?? ''),
                'email' => $e['email'] ?? null,
                'hire_date' => $e['hireDate'] ?? null,
            ])
            ->filter(fn (array $e) => $e['external_id'] !== '')
            ->values()
            ->all();
    }

    public function fetchPayslips(?Carbon $since = null): array
    {
        $response = $this->request('get', '/paystubs', $since ? ['since' => $since->toDateString()] : []);

        return collect($this->unwrapList($response))
            ->map(function (array $p) {
                $items = collect($p['items'] ?? $p['lineItems'] ?? [])
                    ->map(fn (array $i) => [
                        'label' => (string) ($i['label'] ?? $i['description'] ?? 'Item'),
                        'type' => $this->mapItemType($i['type'] ?? null),
                        'amount' => (float) ($i['amount'] ?? 0),
                    ])
                    ->all();

                return [
                    'external_id' => (string) ($p['id'] ?? $p['payStubId'] ?? ''),
                    'employee_external_id' => (string) ($p['employeeId'] ?? ''),
                    'period_start' => $p['periodStart'] ?? $p['payPeriodStart'] ?? null,
                    'period_end' => $p['periodEnd'] ?? $p['payPeriodEnd'] ?? null,
                    'pay_date' => $p['payDate'] ?? null,
                    'regular_hours' => (float) ($p['regularHours'] ?? 0),
                    'overtime_hours' => (float) ($p['overtimeHours'] ?? 0),
                    'gross_pay' => (float) ($p['grossPay'] ?? 0),
                    'net_pay' => (float) ($p['netPay'] ?? 0),
                    'federal_tax' => (float) ($p['federalTax'] ?? 0),
                    'state_tax' => (float) ($p['stateTax'] ?? 0),
                    'fica_ss' => (float) ($p['socialSecurityTax'] ?? 0),
                    'fica_medicare' => (float) ($p['medicareTax'] ?? 0),
                    'total_deductions' => (float) ($p['totalDeductions'] ?? 0),
                    'items' => $items,
                ];
            })
            ->filter(fn (array $p) => $p['external_id'] !== '' && $p['employee_external_id'] !== '')
            ->values()
            ->all();
    }

    /** The API may wrap lists as {"data": [...]} or return a bare array — accept either. */
    private function unwrapList(Response $response): array
    {
        $body = $response->json();

        return $body['data'] ?? (is_array($body) ? $body : []);
    }

    private function mapItemType(?string $raw): string
    {
        return match (strtolower((string) $raw)) {
            'deduction' => 'deduction',
            'tax' => 'tax',
            'reimbursement' => 'reimbursement',
            default => 'earning',
        };
    }

    private function request(string $method, string $path, array $query = []): Response
    {
        try {
            $response = Http::withToken((string) $this->integration->api_key)
                ->baseUrl($this->integration->base_url ?: self::DEFAULT_BASE_URL)
                ->timeout(20)
                ->acceptJson()
                ->{$method}($path, $query);
        } catch (\Throwable $e) {
            Log::error("Payroll Relief request failed [{$method} {$path}]: ".$e->getMessage());
            throw new PayrollProviderException('Could not reach Payroll Relief. Please check your connection and try again.');
        }

        if ($response->failed()) {
            Log::error("Payroll Relief request failed [{$method} {$path}]: HTTP {$response->status()} — ".$response->body());
            throw new PayrollProviderException(
                'Payroll Relief returned an error (HTTP '.$response->status().'). '
                .'Double-check the API key, or contact AccountantsWorld support if this persists.'
            );
        }

        return $response;
    }
}
