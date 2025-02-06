<?php

namespace App\Http\Controllers;

use App\Models\Sighting;
use App\Models\StrandedIncident;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GenerateReportController extends Controller
{
    public function clusterMapIndex()
    {
        $sightings = Sighting::with('sightedSpecies.species')->where('is_active', true)
            ->where('report_status', 'verified')->get();
        $strandedIncidents = StrandedIncident::with('strandedSpecies.species')->where('is_active', true)
            ->where('report_status', 'resolved')->get();

        $processedSightings = $sightings->flatMap(function ($sighting) {
            return $sighting->sightedSpecies->map(function ($sightedSpecies) use ($sighting) {
                return [
                    'latitude' => $sighting->latitude,
                    'longitude' => $sighting->longitude,
                    'date' => $sighting->date,
                    'type' => 'sighting',
                    'species_id' => $sightedSpecies->species->id,
                    'species_name' => $sightedSpecies->species->name ?? 'Unknown',
                    'category' => $sightedSpecies->species->category ?? 'Unknown',
                ];
            });
        });

        $processedStrandedIncidents = $strandedIncidents->flatMap(function ($incident) {
            return $incident->strandedSpecies->map(function ($strandedSpecies) use ($incident) {
                $status = $strandedSpecies->condition_code == 1 ? 'Alive' : ($strandedSpecies->condition_code >= 2 && $strandedSpecies->condition_code <= 5 ? 'Dead' : 'Unknown');
                return [
                    'latitude' => $strandedSpecies->latitude,
                    'longitude' => $strandedSpecies->longitude,
                    'date' => $incident->date,
                    'type' => 'stranded',
                    'status' => $strandedSpecies->condition_code,
                    'species_id' => $strandedSpecies->species->id,
                    'species_name' => $strandedSpecies->species->name ?? 'Unknown',
                    'category' => $strandedSpecies->species->category ?? 'Unknown',
                ];
            });
        });

        $combinedData = $processedSightings->merge($processedStrandedIncidents);

        return Inertia::render('generate-report/cluster-map/index', [
            'incidents' => $combinedData,
        ]);
    }

    public function summaryReportIndex()
    {
        $sightings = Sighting::with('sightedSpecies')->get();
        $strandedIncidents = StrandedIncident::with('strandedSpecies')->get();

        // Process data for analytics
        $analyticsData = $this->processAnalyticsData($sightings, $strandedIncidents);

        return Inertia::render('generate-report/summary-report/index', [
            'analyticsData' => $analyticsData,
        ]);
    }

    private function processAnalyticsData($sightings, $strandedIncidents)
    {
        // Initialize analytics data
        $analyticsData = [
            'yearlyTrends' => [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => 'Incidents',
                        'data' => [],
                    ],
                ],
            ],
            'municipalityDistribution' => [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => 'Distribution',
                        'data' => [],
                    ],
                ],
            ],
            'conditionsFrequency' => [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => 'Frequency',
                        'data' => [],
                    ],
                ],
            ],
            'totalIncidents' => 0,
            'totalSpeciesInvolved' => 0,
            'topCommonSpecies' => [],
            'totalFalseReports' => 0,
        ];

        // Process sightings and stranded incidents data
        foreach ($sightings as $sighting) {
            // Process each sighting
            // ... (add logic to update analyticsData based on sighting)
        }

        foreach ($strandedIncidents as $incident) {
            // Process each stranded incident
            // ... (add logic to update analyticsData based on incident)
        }

        // Calculate top 5 common species
        // ... (add logic to calculate top 5 common species)

        return $analyticsData;
    }
}
