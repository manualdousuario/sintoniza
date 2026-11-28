<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sintoniza:purge-inactive-users')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('sintoniza:prune-episode-actions')->dailyAt('04:00')->withoutOverlapping();
