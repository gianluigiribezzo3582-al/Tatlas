<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep studios in sync with OpenStreetMap (idempotent; Overpass is a shared service,
// so once a week at night is plenty).
Schedule::command('tatlas:import-osm')->weeklyOn(1, '3:17')->withoutOverlapping()->onOneServer();
