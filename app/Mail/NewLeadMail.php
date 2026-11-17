<?php

namespace App\Mail;

use App\Models\Lead;
use App\Support\Leads\LeadTypes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * FR-LEAD-008b: the optional email copy of a new (or newly assigned) lead for the team. Plain text;
 * the visitor's address is set as Reply-To when they gave one. Sent only when email is enabled.
 */
class NewLeadMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead, public bool $assigned = false)
    {
        $this->onQueue('notifications');
    }

    public function envelope(): Envelope
    {
        $label = LeadTypes::label($this->lead->type);
        $subject = $this->assigned
            ? "Lead assigned to you: {$this->lead->name}"
            : "New lead ({$label}): {$this->lead->name}";

        return new Envelope(
            subject: preg_replace('/\s+/', ' ', $subject) ?? $subject,
            replyTo: $this->lead->email !== null ? [$this->lead->email] : [],
        );
    }

    public function content(): Content
    {
        $this->lead->loadMissing('project');

        return new Content(text: 'emails.lead-new', with: [
            'lead' => $this->lead,
            'typeLabel' => LeadTypes::label($this->lead->type),
            'leadUrl' => route('admin.leads.index', ['lead' => $this->lead->id]),
            'assigned' => $this->assigned,
        ]);
    }
}
