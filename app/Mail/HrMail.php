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

    private string $viewName;
    private array  $mailData;

    public function __construct(string $viewName, array $viewData)
    {
        $this->viewName = $viewName;
        $this->mailData = $viewData;
    }

    public function envelope(): Envelope
    {
        $companyName = $this->mailData['companyName'] ?? 'HR Platform';
        $subject     = $this->mailData['subject'] ?? 'HR Notification';

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
            with: $this->mailData,
        );
    }
}
