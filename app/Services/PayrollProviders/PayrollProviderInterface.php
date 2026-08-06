<?php

namespace App\Services\PayrollProviders;

use App\Exceptions\PayrollProviderException;
use Carbon\Carbon;

/**
 * Contract for a third-party payroll system connector. Every provider (Payroll Relief today,
 * others potentially later) normalizes its own data into the same shapes here, so the rest of
 * the app — PayrollSyncService, the admin UI — never talks to a specific provider's API directly.
 */
interface PayrollProviderInterface
{
    /** Verify the stored credentials actually work. @throws PayrollProviderException on failure. */
    public function testConnection(): void;

    /**
     * @return array<int, array{external_id: string, employee_code: ?string, first_name: string,
     *     last_name: string, email: ?string, hire_date: ?string}>
     */
    public function fetchEmployees(): array;

    /**
     * @return array<int, array{external_id: string, employee_external_id: string, period_start: ?string,
     *     period_end: ?string, pay_date: ?string, regular_hours: float, overtime_hours: float,
     *     gross_pay: float, net_pay: float, federal_tax: float, state_tax: float, fica_ss: float,
     *     fica_medicare: float, total_deductions: float,
     *     items: array<int, array{label: string, type: string, amount: float}>}>
     */
    public function fetchPayslips(?Carbon $since = null): array;
}
