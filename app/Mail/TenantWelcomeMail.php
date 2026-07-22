<?php

namespace App\Mail;

use App\Models\Main\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $adminName,
        public string $password,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ' Welcome to Lockmytimes — Your workspace is ready!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-welcome',
            with: [
                'tenant'    => $this->tenant,
                'adminName' => $this->adminName,
                'password'  => $this->password,
                'adminUrl'  => $this->tenant->adminUrl().'/login',
                'trialEnds' => $this->tenant->trial_ends_at?->format('F j, Y'),
            ],
        );
    }
}