<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('warranties:mark-expired')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('odoo:retry-failed-validations')->hourly()->withoutOverlapping();
Schedule::command('notifications:retry-failed')->hourly()->withoutOverlapping();
Schedule::command('odoo:import-pos-sales')->everyFifteenMinutes()->withoutOverlapping();
