<?php

namespace App\Jobs;

use App\Mail\TenantWelcomeMail;
use App\Models\Main\Tenant;
use App\Models\Tenant\User;
use App\Services\TenantManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class ProvisionTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300; // 5 minutes max

    public function __construct(
        public Tenant $tenant,
        public string $adminName,
        public string $password = '',
    ) {
        // Generate a strong random password if none given
        if (empty($this->password)) {
            $this->password = Str::password(12);
        }
    }

    public function handle(TenantManager $manager): void
    {
        Log::info("Starting tenant provisioning for: {$this->tenant->slug}");

        try {
            // Provision the DB (creates DB, runs migrations, seeds defaults)
            $manager->provision($this->tenant);

            // Create the admin user inside the tenant DB
            $manager->runFor($this->tenant, function () {
                User::create([
                    'name'              => $this->adminName,
                    'email'             => $this->tenant->contact_email,
                    'password'          => Hash::make($this->password),
                    'is_active'         => true,
                    'must_change_password' => true,   // force password change on first login
                    'email_verified_at' => now(),
                ])->assignRole('Tenant Admin');
            });

            // Send welcome email
            Mail::to($this->tenant->contact_email)->send(
                new TenantWelcomeMail($this->tenant, $this->adminName, $this->password)
            );

            Log::info("Tenant provisioned successfully: {$this->tenant->slug}");

        } catch (Throwable $e) {
            Log::error("Tenant provisioning failed for {$this->tenant->slug}: ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // re-throw so the job retries
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error("Tenant provisioning permanently failed for {$this->tenant->slug}: ".$e->getMessage());

        // Mark tenant as failed so support can investigate
        $this->tenant->update(['status' => 'pending']);
    }
}