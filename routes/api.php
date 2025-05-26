<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health-check', \App\Http\Controllers\Api\HealthCheckController::class);

