<?php

use App\Http\Controllers\WeatherController;

Route::get('/weather', [WeatherController::class, 'getWeather']);
