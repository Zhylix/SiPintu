<?php

namespace App\Console\Commands;

use App\Jobs\SyncSijunaTeachersJob;
use Illuminate\Console\Command;

class SyncSijunaTeachersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sijuna:sync-teachers {--sync-now : Run command synchronously instead of queuing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize teacher identity data from SIJUNA API to Gateway local cache and DB';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting SIJUNA teacher identity synchronization...');

        if ($this->option('sync-now')) {
            SyncSijunaTeachersJob::dispatchSync();
        } else {
            SyncSijunaTeachersJob::dispatch();
        }

        $this->info('SIJUNA teacher synchronization job dispatched successfully.');

        return Command::SUCCESS;
    }
}
