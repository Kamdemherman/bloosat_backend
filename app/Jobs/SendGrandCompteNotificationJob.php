<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Mail\RedevanceReminderMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Mail, Log};

class SendGrandCompteNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param int $daysOffset positive = days BEFORE end, negative = days AFTER end
     */
    public function __construct(private int $daysOffset) {}

    public function handle(): void
    {
        $targetDate = $this->daysOffset > 0
            ? now()->addDays($this->daysOffset)->toDateString()
            : now()->subDays(abs($this->daysOffset))->toDateString();

        $subscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereDate('current_cycle_end', $targetDate)
            ->whereHas('client', fn ($q) =>
                $q->where('type', 'grand_compte')
            )
            ->with(['client.commercial', 'redevances.invoice'])
            ->get();

        foreach ($subscriptions as $subscription) {
            $client = $subscription->client;

            $unpaidRedevance = $subscription->redevances
                ->where('status', 'non_payee')
                ->sortByDesc('created_at')
                ->first();

            if ($unpaidRedevance) {
                // Send to accounting team
                $accountingEmail = config('services.accounting_email');
                if ($accountingEmail) {
                    try {
                        Mail::to($accountingEmail)->queue(
                            new RedevanceReminderMail($client, $subscription, $unpaidRedevance, $this->daysOffset, true)
                        );
                        Log::info("Grand compte notification sent to accounting for client #{$client->id} (offset: {$this->daysOffset}d)");
                    } catch (\Exception $e) {
                        Log::error("Failed to send grand compte notification for client #{$client->id}: {$e->getMessage()}");
                    }
                }
            }
        }
    }
}