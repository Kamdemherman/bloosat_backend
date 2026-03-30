<?php

namespace App\Jobs;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\Log;

class GenerateRedevanceInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Generate invoices for subscriptions expiring in exactly 7 days
        // (so the reminder job can attach it)
        $subscriptions = Subscription::where('status', 'active')
            ->whereDate('current_cycle_end', now()->addDays(7)->toDateString())
            ->whereDoesntHave('redevances', fn ($q) =>
                $q->where('period_start', '>', now()->toDateString())
            )
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                $redevance = $subscription->generateNextRedevance();
                Log::info("Generated redevance #{$redevance->id} for subscription #{$subscription->id}");
            } catch (\Exception $e) {
                Log::error("Failed to generate redevance for subscription #{$subscription->id}: {$e->getMessage()}");
            }
        }
    }
}
