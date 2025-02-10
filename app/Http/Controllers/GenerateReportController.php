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
        return Inertia::render('generate-report/cluster-map/index');
    }

    public function summaryReportIndex()
    {
        return Inertia::render('generate-report/summary-report/index');
    }

    public function preprocesseddata(){
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

    }

}
