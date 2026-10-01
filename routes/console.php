<?php

use Illuminate\Support\Facades\Schedule;

// Run locally with "php artisan schedule:work"; in production, a cron entry
// calls "php artisan schedule:run" every minute.

Schedule::command('tickets:close-stale')
    ->daily()
    ->withoutOverlapping();

// Expired Sanctum tokens are rejected but stay in the table until pruned.
Schedule::command('sanctum:prune-expired --hours=24')
    ->daily();
