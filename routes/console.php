<?php

use App\Services\Communication\RecoverCommunicationWork;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('communications:recover', function (RecoverCommunicationWork $recovery): void {
    $recovery->handle();
})->purpose('Republish overdue communication work and reconcile expired claims');

Schedule::command('communications:recover')->everyMinute()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
