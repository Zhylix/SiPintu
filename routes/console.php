<?php

use App\Jobs\CheckApplicationHealthJob;
use App\Jobs\SyncSijunaStudentsJob;
use Illuminate\Support\Facades\Schedule;

// Schedule periodic SIJUNA student identity synchronization (every 6 hours)
Schedule::job(new SyncSijunaStudentsJob())->everySixHours();

// Schedule application health checks (every 15 minutes)
Schedule::job(new CheckApplicationHealthJob())->everyFifteenMinutes();
