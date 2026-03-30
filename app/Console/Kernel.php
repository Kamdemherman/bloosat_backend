<?php

namespace App\Console;

use App\Jobs\{
    GenerateRedevanceInvoicesJob,
    SendRedevanceReminderJob,
    SendGrandCompteNotificationJob,
    AutoSuspendUnpaidClientsJob,
};
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Generate next-cycle redevance invoices 7 days before expiry
        $schedule->job(new GenerateRedevanceInvoicesJob)
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->name('generate-redevances');

        // ── Ordinaire clients: reminders BEFORE cycle end ──────
        $schedule->job(new SendRedevanceReminderJob('ordinaire', 7))
            ->dailyAt('07:00')
            ->name('reminder-ordinaire-7d');

        $schedule->job(new SendRedevanceReminderJob('ordinaire', 2))
            ->dailyAt('07:05')
            ->name('reminder-ordinaire-2d');

        // ── Grand compte clients: reminders AFTER cycle end ─────
        $schedule->job(new SendRedevanceReminderJob('grand_compte', -2))
            ->dailyAt('07:10')
            ->name('reminder-gc-2d-late');

        $schedule->job(new SendRedevanceReminderJob('grand_compte', -7))
            ->dailyAt('07:15')
            ->name('reminder-gc-7d-late');

        $schedule->job(new SendRedevanceReminderJob('grand_compte', -15))
            ->dailyAt('07:20')
            ->name('reminder-gc-15d-late');

        // ── Grand compte clients: accounting notifications ─────
        $schedule->job(new SendGrandCompteNotificationJob(-2))
            ->dailyAt('07:25')
            ->name('notify-accounting-gc-2d-late');

        $schedule->job(new SendGrandCompteNotificationJob(-7))
            ->dailyAt('07:30')
            ->name('notify-accounting-gc-7d-late');

        $schedule->job(new SendGrandCompteNotificationJob(-15))
            ->dailyAt('07:35')
            ->name('notify-accounting-gc-15d-late');

        // ── Auto-suspend unpaid ordinaire clients (hourly) ──────
        $schedule->job(new AutoSuspendUnpaidClientsJob)
            ->hourly()
            ->withoutOverlapping()
            ->name('auto-suspend');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }

    protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class, // CSRF only for web
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],

    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
}
