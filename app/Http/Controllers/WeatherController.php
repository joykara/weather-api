<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
        ]);

        // Handle failed API responses
        if ($currentWeatherResponse->failed() || $forecastResponse->failed()) {
            return response()->json([
                'error' => 'Failed to fetch weather data',
                'current' => $currentWeatherResponse->json(),
                'forecast' => $forecastResponse->json(),
            ], 500);
        }

        $forecastData = $forecastResponse->json()['list'];

        // Group forecast by day
        $dailyForecasts = [];

        foreach ($forecastData as $entry) {
            $date = Carbon::parse($entry['dt_txt'])->format('Y-m-d');

            // Skip today
            if ($date === Carbon::today()->format('Y-m-d')) {
                continue;
            }

            if (!isset($dailyForecasts[$date])) {
                $dailyForecasts[$date] = [
                    'temps' => [],
                    'descriptions' => [],
                    'icons' => [],
                ];
            }

            $dailyForecasts[$date]['temps'][] = $entry['main']['temp'];
            $dailyForecasts[$date]['descriptions'][] = $entry['weather'][0]['description'];
            $dailyForecasts[$date]['icons'][] = $entry['weather'][0]['icon'];
        }

        // Next 3 days
        $summary = [];
        foreach (array_slice($dailyForecasts, 0, 3) as $date => $info) {
            $summary[] = [
                'date' => $date,
                'min_temp' => round(min($info['temps']), 1),
                'max_temp' => round(max($info['temps']), 1),
                'description' => ucfirst(mostFrequent($info['descriptions'])),
                'icon' => $info['icons'][0],
            ];
        }

        // Combine the data
        $data = [
            'current' => $currentWeatherResponse->json(),
            'forecast' => $summary,
        ];

        return response()->json($data);
    }
}

function mostFrequent(array $array): string
{
    $counts = array_count_values($array);
    arsort($counts);
    return array_key_first($counts);
}
