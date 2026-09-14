<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('pics:send-weekly-summary')
    ->mondays()
    ->at('07:00')
    ->timezone('America/Bogota')
    ->withoutOverlapping();

Schedule::command('pics:close-expired-followups')
    ->daily()
    ->at('02:00')
    ->timezone('America/Bogota')
    ->withoutOverlapping();

Schedule::command('pics:check-portal-inactivity')
    ->daily()
    ->at('07:30')
    ->timezone('America/Bogota')
    ->withoutOverlapping();

Schedule::command('pics:notify-caregiver-of-inactive-patient-today')
    ->daily()
    ->at('20:00')
    ->timezone('America/Bogota')
    ->withoutOverlapping();

Schedule::command('pics:send-daily-portal-push')
    ->daily()
    ->at('08:00')
    ->timezone('America/Bogota')
    ->withoutOverlapping();

Schedule::command('pics:send-sepsis-monthly-digest')
    ->monthlyOn(1, '07:00')
    ->timezone('America/Bogota')
    ->withoutOverlapping();
