<?php

namespace App\CultureParcelle\Service;

use App\Entity\Culture;

class CalendarService
{
    /**
     * Build calendar events from cultures array
     */
    public function buildCalendarEvents(array $cultures): array
    {
        $events = [];
        
        foreach ($cultures as $culture) {
            // Add planting date event
            if ($culture->getDateSemis()) {
                $events[] = $this->createPlantingEvent($culture);
            }
            
            // Add harvest date event
            if ($culture->getDateRecolte()) {
                $events[] = $this->createHarvestEvent($culture);
            }
        }
        
        return $events;
    }

    /**
     * Create a planting event for calendar
     */
    private function createPlantingEvent(Culture $culture): array
    {
        return [
            'id' => 'semis_' . $culture->getIdCulture(),
            'title' => '🌱 ' . $culture->getNomCulture(),
            'start' => $culture->getDateSemis()->format('Y-m-d'),
            'backgroundColor' => '#52b788',
            'borderColor' => '#2d6a4f',
            'extendedProps' => [
                'type' => 'semis',
                'cultureType' => $culture->getTypeCulture(),
                'cultureId' => $culture->getIdCulture()
            ]
        ];
    }

    /**
     * Create a harvest event for calendar
     */
    private function createHarvestEvent(Culture $culture): array
    {
        return [
            'id' => 'recolte_' . $culture->getIdCulture(),
            'title' => '🌾 ' . $culture->getNomCulture(),
            'start' => $culture->getDateRecolte()->format('Y-m-d'),
            'backgroundColor' => '#f77f00',
            'borderColor' => '#d62828',
            'extendedProps' => [
                'type' => 'recolte',
                'cultureType' => $culture->getTypeCulture(),
                'cultureId' => $culture->getIdCulture()
            ]
        ];
    }
}
