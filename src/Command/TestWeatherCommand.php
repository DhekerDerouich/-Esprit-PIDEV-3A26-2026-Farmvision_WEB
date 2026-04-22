<?php

namespace App\Command;

use App\CultureParcelle\Service\WeatherService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test:weather',
    description: 'Test the OpenWeather API integration',
)]
class TestWeatherCommand extends Command
{
    public function __construct(
        private WeatherService $weatherService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('lat', null, InputOption::VALUE_OPTIONAL, 'Latitude', 48.8566)
            ->addOption('lon', null, InputOption::VALUE_OPTIONAL, 'Longitude', 2.3522)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $lat = (float) $input->getOption('lat');
        $lon = (float) $input->getOption('lon');

        $io->title('Testing OpenWeather API');
        $io->info(sprintf('Fetching weather for coordinates: %.4f, %.4f', $lat, $lon));

        try {
            $weatherData = $this->weatherService->getWeatherByCoordinates($lat, $lon);

            if (!$weatherData) {
                $io->error('Failed to fetch weather data. Check logs for details.');
                return Command::FAILURE;
            }

            $emoji = $this->weatherService->getWeatherEmoji($weatherData['icon']);

            $io->success('Weather data retrieved successfully!');
            
            $io->section('Weather Information');
            $io->table(
                ['Property', 'Value'],
                [
                    ['Temperature', $weatherData['temperature'] . '°C'],
                    ['Feels Like', $weatherData['feels_like'] . '°C'],
                    ['Description', $emoji . ' ' . $weatherData['description']],
                    ['Humidity', $weatherData['humidity'] . '%'],
                    ['Pressure', $weatherData['pressure'] . ' hPa'],
                    ['Wind Speed', $weatherData['wind_speed'] . ' km/h'],
                    ['Wind Direction', $weatherData['wind_deg'] . '°'],
                    ['Clouds', $weatherData['clouds'] . '%'],
                    ['Visibility', $weatherData['visibility'] ? $weatherData['visibility'] . ' km' : 'N/A'],
                ]
            );

            $io->section('Raw Data');
            $io->writeln(json_encode($weatherData, JSON_PRETTY_PRINT));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Exception occurred: ' . $e->getMessage());
            $io->writeln('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
