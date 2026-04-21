<?php
// src/Service/MeteoService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MeteoService
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getSunriseSunset(float $lat, float $lng, ?string $date = null): array
    {
        $dateParam = $date ?? date('Y-m-d');
        $url = sprintf(
            'https://api.sunrise-sunset.org/json?lat=%f&lng=%f&date=%s&formatted=0',
            $lat, $lng, $dateParam
        );

        try {
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();
            
            if ($data['status'] === 'OK') {
                return [
                    'sunrise' => (new \DateTime($data['results']['sunrise']))->format('H:i'),
                    'sunset' => (new \DateTime($data['results']['sunset']))->format('H:i'),
                    'day_length' => gmdate('H:i', $data['results']['day_length']),
                ];
            }
        } catch (\Exception $e) {
            // Log l'erreur si nécessaire
        }
        
        // Valeurs par défaut (Tunisie - environ 12h de jour)
        return [
            'sunrise' => '06:30',
            'sunset' => '18:30',
            'day_length' => '12:00'
        ];
    }

    public function getWeatherForecast(float $lat, float $lng): array
    {
        $url = sprintf(
            'https://api.open-meteo.com/v1/forecast?latitude=%f&longitude=%f&current=temperature_2m,relative_humidity_2m,precipitation,wind_speed_10m&daily=temperature_2m_max,temperature_2m_min&timezone=auto',
            $lat, $lng
        );

        try {
            $response = $this->httpClient->request('GET', $url);
            return $response->toArray();
        } catch (\Exception $e) {
            return [
                'current' => [
                    'temperature_2m' => '--',
                    'wind_speed_10m' => '--'
                ],
                'daily' => [
                    'temperature_2m_max' => ['--'],
                    'temperature_2m_min' => ['--']
                ]
            ];
        }
    }
}
