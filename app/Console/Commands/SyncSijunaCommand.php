<?php

namespace App\Console\Commands;

use App\Jobs\SyncSijunaStudentsJob;
use App\Jobs\SyncSijunaTeachersJob;
use Illuminate\Console\Command;

class SyncSijunaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sijuna:sync {--sync-now : Run command synchronously instead of queuing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize all (student & teacher) identity data from SIJUNA API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting full SIJUNA (Students & Teachers) synchronization...');

        if ($this->option('sync-now')) {
            SyncSijunaStudentsJob::dispatchSync();
            SyncSijunaTeachersJob::dispatchSync();
        } else {
            SyncSijunaStudentsJob::dispatch();
            SyncSijunaTeachersJob::dispatch();
        }

        $this->info('All SIJUNA synchronization jobs dispatched successfully.');

        return Command::SUCCESS;
    }
}
