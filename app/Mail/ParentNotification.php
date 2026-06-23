<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Generic branded email to a parent. Reused for the parent-facing events
 * (attendance check-in, payment receipt, leave decision). Queued so the
 * triggering action stays fast.
 *
 * @param string $heading  Big heading line.
 * @param array  $lines    Paragraphs of body text.
 * @param array  $details  Optional key => value rows (used for receipts).
 */
class ParentNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $heading,
        public array $lines = [],
        public array $details = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.parent-notification');
    }
}
