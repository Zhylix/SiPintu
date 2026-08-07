<?php

use App\Jobs\CheckApplicationHealthJob;
use App\Jobs\SyncSijunaStudentsJob;
use App\Jobs\SyncSijunaTeachersJob;
use Illuminate\Support\Facades\Schedule;

// Schedule periodic SIJUNA identity synchronization for students and teachers (every 6 hours)
Schedule::job(new SyncSijunaStudentsJob)->everySixHours();
Schedule::job(new SyncSijunaTeachersJob)->everySixHours();

// Schedule application health checks (every 15 minutes)
Schedule::job(new CheckApplicationHealthJob)->everyFifteenMinutes();
