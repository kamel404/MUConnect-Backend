<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ProcessQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-queue {--tries=3 : Number of times to attempt a job before logging it failed} {--timeout=120 : Seconds a job may run before timing out} {--sleep=3 : Seconds to wait before checking queue for jobs} {--max-jobs=0 : Maximum number of jobs to process before stopping}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process queued jobs for AI content generation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting queue worker...');

        // Run the Laravel queue:work command with our parameters
        Artisan::call('queue:work', [
            'connection' => 'database',
            'queue' => 'default',
            '--tries' => $this->option('tries'),
            '--timeout' => $this->option('timeout'),
            '--sleep' => $this->option('sleep'),
            '--max-jobs' => $this->option('max-jobs'),
            '--verbose' => true,
            '--no-interaction' => true
        ]);

        return Command::SUCCESS;
    }
}
