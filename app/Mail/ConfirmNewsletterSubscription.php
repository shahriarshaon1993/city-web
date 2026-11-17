<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Double opt-in mail (spec/leads.md "Newsletter"): one link that confirms the address. Plain text,
 * sent only when email is enabled in Settings → Notifications.
 */
class ConfirmNewsletterSubscription extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $subscriber)
    {
        $this->onQueue('notifications');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Please confirm your subscription');
    }

    public function content(): Content
    {
        return new Content(text: 'emails.newsletter-confirm', with: [
            'confirmUrl' => route('site.newsletter.confirm', ['token' => $this->subscriber->token]),
            'unsubscribeUrl' => route('site.newsletter.unsubscribe', ['token' => $this->subscriber->token]),
        ]);
    }
}
