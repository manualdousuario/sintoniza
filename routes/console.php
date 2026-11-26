<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sintoniza:update-feeds')->hourly();
Schedule::command('sintoniza:purge-inactive-users')->dailyAt('03:00')->withoutOverlapping();
