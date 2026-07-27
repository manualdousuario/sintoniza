<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sintoniza:update-feeds')->hourly();
