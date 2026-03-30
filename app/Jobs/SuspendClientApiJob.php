<?php

namespace App\Jobs;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Http, Log};

class SuspendClientApiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(public Client $client) {}

    public function handle(): void
    {
        // Try your custom suspension API
        try {
            Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.suspension_api.key'),
                    'Content-Type'  => 'application/json',
                ])
                ->post(config('services.suspension_api.url') . '/suspend', [
                    'client_id' => $this->client->id,
                    'client_name' => $this->client->display_name,
                    'client_email' => $this->client->email,
                    'action' => 'suspend',
                    'reason' => 'Non-paiement automatique',
                    'suspended_at' => now()->toISOString(),
                ]);
            Log::info("Custom API: Client #{$this->client->id} suspended successfully.");
            return;
        } catch (\Exception $e) {
            Log::warning("Custom suspension API failed for client #{$this->client->id}: {$e->getMessage()}");
        }

        // Fallback: KAF API
        try {
            Http::timeout(15)
                ->withHeaders(['Authorization' => 'Bearer ' . config('services.kaf.key')])
                ->post(config('services.kaf.url') . '/clients/suspend', [
                    'client_id' => $this->client->id,
                    'action'    => 'suspend',
                ]);
            Log::info("KAF: Client #{$this->client->id} suspended.");
            return;
        } catch (\Exception $e) {
            Log::warning("KAF API failed for client #{$this->client->id}: {$e->getMessage()}");
        }

        // Fallback: Iway API
        try {
            Http::timeout(15)
                ->withHeaders(['X-API-Key' => config('services.iway.key')])
                ->post(config('services.iway.url') . '/suspend', [
                    'client' => $this->client->id,
                ]);
            Log::info("Iway: Client #{$this->client->id} suspended.");
        } catch (\Exception $e) {
            Log::error("Iway API also failed for client #{$this->client->id}: {$e->getMessage()}");
            $this->fail($e);
        }
    }
}
