<?php

namespace App\Mail;

use App\Models\Encaissement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Encaissement $encaissement) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reçu de paiement — {$this->encaissement->reference}"
        );
    }

    public function content(): Content
    {
        $isComplete = $this->encaissement->getCompletionStatus() === 'complet';
        $invoice = $this->encaissement->invoice;
        $remaining = max(0, $invoice->total - $this->encaissement->amount);

        return new Content(
            view: 'emails.payment-receipt',
            with: [
                'encaissement' => $this->encaissement,
                'client'       => $this->encaissement->client,
                'invoice'      => $invoice,
                'amount'       => number_format($this->encaissement->amount, 0, ',', ' ') . ' FCFA',
                'date'         => $this->encaissement->payment_date->format('d/m/Y'),
                'isComplete'   => $isComplete,
                'remaining'    => number_format($remaining, 0, ',', ' ') . ' FCFA',
                'status'       => $isComplete ? 'COMPLET' : 'INCOMPLET',
            ]
        );
    }
}
