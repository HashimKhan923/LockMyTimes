<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HrMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $viewName,
        public readonly array  $viewData,
    ) {}

    public function envelope(): Envelope
    {
        $companyName = $this->viewData['companyName'] ?? 'HR Platform';
        $subject     = $this->viewData['subject'] ?? 'HR Notification';

        return new Envelope(
            from:    new Address('info@lockmytimes.com', $companyName . ' HR'),
            replyTo: [new Address('support@lockmytimes.com', 'Support')],
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->viewName,
            with: $this->viewData,
        );
    }
}
