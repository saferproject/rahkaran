<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agxic:clear-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'clear laravel logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        exec('echo > ' . storage_path('logs/laravel.log'));
        $this->info('Logs have been cleared');
    }
}
