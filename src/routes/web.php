<?php

use DigitalPulse\KeepMesynced\app\Http\Controllers\KeepMeSyncedController;
use Illuminate\Support\Facades\Route;

Route::post('git/updated', [KeepMeSyncedController::class, 'hook']);