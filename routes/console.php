<?php

use App\Jobs\CheckApplicationHealthJob;
use App\Jobs\SyncSijunaStudentsJob;
use App\Jobs\SyncSijunaTeachersJob;
use Illuminate\Support\Facades\Schedule;

// Schedule periodic SIJUNA identity synchronization for students and teachers (every 3 days at 00:00)
Schedule::job(new SyncSijunaStudentsJob)->cron('0 0 */3 * *');
Schedule::job(new SyncSijunaTeachersJob)->cron('0 0 */3 * *');

// Schedule application health checks (every 15 minutes)
Schedule::job(new CheckApplicationHealthJob)->everyFifteenMinutes();
