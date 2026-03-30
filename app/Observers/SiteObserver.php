<?php

namespace App\Observers;

use App\Models\Site;
use App\Models\Client;

class SiteObserver
{
    /**
     * Quand un site est créé, promouvoir le prospect en client
     */
    public function created(Site $site): void
    {
        $this->promoteClientIfEligible($site->client);
    }

    /**
     * Vérifier et promouvoir le client s'il est prospect
     */
    private function promoteClientIfEligible(Client $client): void
    {
        // Si le client n'est pas déjà client et a au moins un site
        if ($client->isProspect() && $client->sites()->exists()) {
            $client->promoteToClient();
        }
    }
}
