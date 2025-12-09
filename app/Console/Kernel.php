<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Task reminder (per menit)
        $schedule->command('task:reminder')
            ->everyMinute()
            ->withoutOverlapping();

        // Task overdue (per menit)
        $schedule->command('task:overdue')
            ->everyMinute()
            ->withoutOverlapping();

        // Notifikasi Daily General – Pagi & Malam
        $schedule->command('notif:daily-general')
            ->timezone('Asia/Jakarta')
            ->dailyAt('07:00')
            ->withoutOverlapping();

        $schedule->command('notif:daily-general')
            ->timezone('Asia/Jakarta')
            ->dailyAt('19:00')
            ->withoutOverlapping();

        // Notifikasi Daily Overdue – Pagi & Malam
        $schedule->command('notif:daily-overdue')
            ->timezone('Asia/Jakarta')
            ->dailyAt('07:00')
            ->withoutOverlapping();

        $schedule->command('notif:daily-overdue')
            ->timezone('Asia/Jakarta')
            ->dailyAt('19:00')
            ->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
