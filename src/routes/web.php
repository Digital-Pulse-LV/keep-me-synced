<?php

use DigitalPulse\KeepMeSynced\app\Http\Controllers\KeepMeSyncedController;
use Illuminate\Support\Facades\Route;

Route::post('keep-me-synced/hook', [KeepMeSyncedController::class, 'hook']);