<?php

namespace App\CultureParcelle\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class WeatherService
{
    private const API_URL = 'https://api.openweathermap.org/data/2.5/weather';
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $apiKey
    ) {}

    /**
     * Get weather data for a specific location
     * 
     * @param float $latitude
     * @param float $longitude
     * @return array|null Weather data or null on failure
     */
    public function getWeatherByCoordinates(float $latitude, float $longitude): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_URL, [
                'query' => [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'appid' => $this->apiKey,
                    'units' => 'metric', // Celsius
                    'lang' => 'fr'
                ],
                'timeout' => 5
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('OpenWeather API returned non-200 status', [
                    'status' => $response->getStatusCode()
                ]);
                return null;
            }

            $data = $response->toArray();
            
            return [
                'temperature' => round($data['main']['temp'], 1),
                'feels_like' => round($data['main']['feels_like'], 1),
                'humidity' => $data['main']['humidity'],
                'pressure' => $data['main']['pressure'],
                'description' => $data['weather'][0]['description'] ?? 'N/A',
                'icon' => $data['weather'][0]['icon'] ?? '01d',
                'wind_speed' => round($data['wind']['speed'] * 3.6, 1), // m/s to km/h
                'wind_deg' => $data['wind']['deg'] ?? 0,
                'clouds' => $data['clouds']['all'] ?? 0,
                'visibility' => isset($data['visibility']) ? round($data['visibility'] / 1000, 1) : null,
                'sunrise' => $data['sys']['sunrise'] ?? null,
                'sunset' => $data['sys']['sunset'] ?? null,
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch weather data', [
                'error' => $e->getMessage(),
                'lat' => $latitude,
                'lon' => $longitude
            ]);
            return null;
        }
    }

    /**
     * Get weather icon emoji based on OpenWeather icon code
     */
    public function getWeatherEmoji(string $iconCode): string
    {
        return match(substr($iconCode, 0, 2)) {
            '01' => '☀️',  // clear sky
            '02' => '⛅',  // few clouds
            '03' => '☁️',  // scattered clouds
            '04' => '☁️',  // broken clouds
            '09' => '🌧️', // shower rain
            '10' => '🌦️', // rain
            '11' => '⛈️',  // thunderstorm
            '13' => '❄️',  // snow
            '50' => '🌫️', // mist
            default => '🌤️'
        };
    }
}
