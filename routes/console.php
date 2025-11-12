<?php

use Illuminate\Support\Facades\Schedule;

// Horizon Metrics
Schedule::command('horizon:snapshot')->everyFiveMinutes();
