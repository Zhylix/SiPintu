<?php

namespace App\Console\Commands;

use App\Jobs\SyncSijunaStudentsJob;
use Illuminate\Console\Command;

class SyncSijunaStudentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sijuna:sync-students {--sync-now : Run command synchronously instead of queuing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize student identity data from SIJUNA API to Gateway local cache and DB';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting SIJUNA student identity synchronization...');

        if ($this->option('sync-now')) {
            SyncSijunaStudentsJob::dispatchSync();
        } else {
            SyncSijunaStudentsJob::dispatch();
        }

        $this->info('SIJUNA synchronization job dispatched successfully.');

        return Command::SUCCESS;
    }
}
