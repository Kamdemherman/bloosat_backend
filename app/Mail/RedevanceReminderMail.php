<?php

namespace App\Mail;

use App\Models\{Client, Subscription, Redevance};
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Queue\SerializesModels;

class RedevanceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client       $client,
        public Subscription $subscription,
        public Redevance    $redevance,
        public int          $daysOffset,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->daysOffset > 0
            ? "Rappel redevance — Échéance dans {$this->daysOffset} jour(s)"
            : "Facture de redevance en retard de " . abs($this->daysOffset) . " jour(s)";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.redevance-reminder',
            with: [
                'client'       => $this->client,
                'subscription' => $this->subscription,
                'redevance'    => $this->redevance,
                'daysOffset'   => $this->daysOffset,
                'amount'       => number_format($this->redevance->invoice->total, 0, ',', ' ') . ' FCFA',
                'dueDate'      => $this->redevance->period_end->format('d/m/Y'),
            ]
        );
    }
}
