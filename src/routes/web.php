<?php

use DigitalPulse\KeepMeSynced\app\Http\Controllers\KeepMeSyncedController;
use Illuminate\Support\Facades\Route;

Route::post('git/updated', [KeepMeSyncedController::class, 'hook']);