<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Mail\SuspensionNotificationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Mail, Log};

class AutoSuspendUnpaidClientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Only suspend ordinaire clients (grands comptes are suspended manually)
        $expiredSubscriptions = Subscription::query()
            ->where('status', 'active')
            ->where('current_cycle_end', '<', now())
            ->whereHas('client', fn ($q) =>
                $q->where('type', 'ordinaire')->where('is_suspended', false)
            )
            ->whereHas('redevances', fn ($q) =>
                $q->where('status', 'non_payee')
            )
            ->with(['client.commercial'])
            ->get();

        foreach ($expiredSubscriptions as $subscription) {
            $client = $subscription->client;

            try {
                $client->update(['is_suspended' => true]);
                dispatch(new SuspendClientApiJob($client));
                $this->sendNotifications($client);
                Log::info("Auto-suspended client #{$client->id} ({$client->display_name})");
            } catch (\Exception $e) {
                Log::error("Failed to suspend client #{$client->id}: {$e->getMessage()}");
            }
        }
    }

    private function sendNotifications($client): void
    {
        $recipients = array_filter([
            $client->commercial_email,
            config('services.accounting_email'),
        ]);

        foreach ($recipients as $email) {
            Mail::to($email)->queue(new SuspensionNotificationMail($client));
        }
    }
}
