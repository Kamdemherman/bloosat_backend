<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Client;

class InvoiceObserver
{
    /**
     * Quand une facture pro-forma passe au statut définitif, promouvoir le client
     */
    public function updated(Invoice $invoice): void
    {
        // Si la facture passe au type définitive (p.ex. conversion de pro-forma)
        if ($invoice->wasChanged('type') && $invoice->type === 'definitive') {
            $this->promoteClientIfEligible($invoice->client);
        }

        // En cas de création d'un status déjà validé comme client
        if ($invoice->wasChanged('status') && $invoice->status === 'payee') {
            $this->promoteClientIfEligible($invoice->client);
        }
    }

    /**
     * Quand une facture définitive est créée, vérifier aussi
     */
    public function created(Invoice $invoice): void
    {
        if ($invoice->type === 'definitive') {
            $this->promoteClientIfEligible($invoice->client);
        }
    }

    /**
     * Vérifier et promouvoir le client s'il est prospect
     */
    private function promoteClientIfEligible(Client $client): void
    {
        // Si le client n'est pas déjà client
        if ($client->isProspect()) {
            // Vérifier s'il a au moins un site OU une facture pro-forma en statut définitif
            $hasSite = $client->sites()->exists();
            $hasDefinitiveInvoice = $client->invoices()
                ->where('type', 'definitive')
                ->exists();

            if ($hasSite || $hasDefinitiveInvoice) {
                $client->promoteToClient();
            }
        }
    }
}
