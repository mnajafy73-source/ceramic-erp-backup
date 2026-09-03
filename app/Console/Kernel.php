<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\FixRawMaterialStock::class,
        \App\Console\Commands\ShoulderFixStock::class,
        \App\Console\Commands\WasteMumFixStock::class,
        \App\Console\Commands\WaxFixStock::class,
        \App\Console\Commands\Glaze1300FixStock::class,
        \App\Console\Commands\WarehouseFixStock::class,
        \App\Console\Commands\FixPackagingStock::class, // ✅ اضافه شد
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}