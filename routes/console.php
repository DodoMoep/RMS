<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Aktivitaetslog: Eintraege aelter als activitylog.delete_records_older_than_days (365) loeschen
Schedule::command('activitylog:clean')->daily();
