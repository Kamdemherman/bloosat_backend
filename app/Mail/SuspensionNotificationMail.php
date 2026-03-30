<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Queue\SerializesModels;

class SuspensionNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Client $client) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "SUSPENSION — {$this->client->display_name}");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.suspension-notification',
            with: [
                'client' => $this->client,
                'date'   => now()->format('d/m/Y H:i'),
            ]
        );
    }
}
