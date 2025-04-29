<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    public function getWeather(Request $request)
    {
        $lat = $request->query('lat');
        $lon = $request->query('lon');
        $apiKey = env('OPENWEATHERMAP_API_KEY');

        if (!$apiKey) {
            return response()->json(['error' => 'API key not configured.'], 400);
        }

        // Fetch current weather data
        $currentWeatherResponse = Http::get("https://api.openweathermap.org/data/2.5/weather", [
            'lat' => $lat,
            'lon' => $lon,
            'appid' => $apiKey,
            'units' => 'metric',
        ]);

        // Fetch 3-day forecast data
        $forecastResponse = Http::get("https://api.openweathermap.org/data/2.5/forecast", [
            'lat' => $lat,
            'lon' => $lon,
            'appid' => $apiKey,
            'units' => 'metric',
            'cnt' => 4,
        ]);

        // Handle failed API responses
        if ($currentWeatherResponse->failed() || $forecastResponse->failed()) {
            return response()->json([
                'error' => 'Failed to fetch weather data',
                'current' => $currentWeatherResponse->json(),
                'forecast' => $forecastResponse->json(),
            ], 500);
        }

        // Combine the datae
        $data = [
            'current' => $currentWeatherResponse->json(),
            'forecast' => $forecastResponse->json()['list'],
        ];

        return response()->json($data);
    }
}