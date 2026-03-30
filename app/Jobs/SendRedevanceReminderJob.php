<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Mail\RedevanceReminderMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Mail, Log};

class SendRedevanceReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param string $clientType  'ordinaire' | 'grand_compte'
     * @param int    $daysOffset  positive = days BEFORE end, negative = days AFTER end
     */
    public function __construct(
        private string $clientType,
        private int    $daysOffset
    ) {}

    public function handle(): void
    {
        $targetDate = $this->daysOffset > 0
            ? now()->addDays($this->daysOffset)->toDateString()
            : now()->subDays(abs($this->daysOffset))->toDateString();

        $subscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereDate('current_cycle_end', $targetDate)
            ->whereHas('client', fn ($q) =>
                $q->where('type', $this->clientType)
            )
            ->with(['client', 'redevances.invoice'])
            ->get();

        foreach ($subscriptions as $subscription) {
            $client = $subscription->client;

            if (! $client->email) {
                continue;
            }

            $unpaidRedevance = $subscription->redevances
                ->where('status', 'non_payee')
                ->sortByDesc('created_at')
                ->first();

            if ($unpaidRedevance) {
                try {
                    Mail::to($client->email)->queue(
                        new RedevanceReminderMail($client, $subscription, $unpaidRedevance, $this->daysOffset)
                    );
                    Log::info("Reminder sent to {$client->email} (offset: {$this->daysOffset}d)");
                } catch (\Exception $e) {
                    Log::error("Failed to send reminder to {$client->email}: {$e->getMessage()}");
                }
            }
        }
    }
}
