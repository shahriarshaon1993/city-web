<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * FR-LEAD-008c: the visitor's automatic reply. Subject and body come from Settings → Notifications
 * with the tokens already filled; plain text. Sent only when the autoresponder is on (default off).
 */
class LeadAutoresponderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $mailSubject, public string $body)
    {
        $this->onQueue('notifications');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(text: 'emails.lead-autoresponder', with: ['body' => $this->body]);
    }
}
