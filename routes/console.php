<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:mark-absents')->dailyAt('23:00');
