<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:mark-absents')->dailyAt('23:00');

// En Vercel (Hobby) el scheduler no corre: la ruta /cron/prune hace lo mismo.
Schedule::command('app:prune-ephemeral')->dailyAt('03:30');
