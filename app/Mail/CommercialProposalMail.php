<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class CommercialProposalMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $proposalSubject;
    public string $proposalBody;
    public ?string $senderName;
    public ?string $senderEmail;

    /**
     * Create a new message instance.
     */
    public function __construct(string $subject, string $body, ?string $senderName = null, ?string $senderEmail = null)
    {
        $this->proposalSubject = $subject;
        $this->proposalBody = $body;
        $this->senderName = $senderName;
        $this->senderEmail = $senderEmail;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->proposalSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.commercial-proposal',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $pdf = Pdf::loadView('emails.commercial-proposal-pdf', [
            'proposalSubject' => $this->proposalSubject,
            'proposalBody' => $this->proposalBody,
            'senderName' => $this->senderName,
            'senderEmail' => $this->senderEmail,
        ]);

        return [
            Attachment::fromData(fn () => $pdf->output(), 'Коммерческое_предложение.pdf')
                    ->withMime('application/pdf'),
        ];
    }
}
