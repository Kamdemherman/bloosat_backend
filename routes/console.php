<?php
// routes/console.php
use App\Jobs\{
    GenerateRedevanceInvoicesJob,
    SendRedevanceReminderJob,
    AutoSuspendUnpaidClientsJob,
};
use Illuminate\Support\Facades\Schedule;

// Manual triggers via artisan (for testing)
Schedule::job(new GenerateRedevanceInvoicesJob)->dailyAt('06:00')->withoutOverlapping();
Schedule::job(new SendRedevanceReminderJob('ordinaire', 7))->dailyAt('07:00');
Schedule::job(new SendRedevanceReminderJob('ordinaire', 2))->dailyAt('07:05');
Schedule::job(new SendRedevanceReminderJob('grand_compte', -2))->dailyAt('07:10');
Schedule::job(new SendRedevanceReminderJob('grand_compte', -7))->dailyAt('07:15');
Schedule::job(new SendRedevanceReminderJob('grand_compte', -15))->dailyAt('07:20');
Schedule::job(new AutoSuspendUnpaidClientsJob)->hourly();
