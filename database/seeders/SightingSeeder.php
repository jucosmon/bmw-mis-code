<?php

namespace Database\Seeders;

use App\Models\SightedSpecies;
use App\Models\Sighting;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SightingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample data for sighted species
        $sightedSpeciesData = [
            [
                'size' => 'large',
                'species_description' => 'A large marine turtle',
                'behavior_observed' => 'Swimming',
                'species_id' => 1, // Replace with actual species ID
            ],
            [
                'size' => 'medium',
                'species_description' => 'A medium-sized shark',
                'behavior_observed' => 'Feeding',
                'species_id' => 2, // Replace with actual species ID
            ],
            [
                'size' => 'small',
                'species_description' => 'A small dolphin',
                'behavior_observed' => 'Jumping',
                'species_id' => 3, // Replace with actual species ID
            ],
        ];

        // Create sightings for each report status
        $reportStatuses = ['pending', 'verified', 'false'];

        foreach ($reportStatuses as $status) {
            for ($i = 0; $i < 3; $i++) { // Create 3 records for each status
                $sighting = Sighting::create([
                    'certainty_level' => 5,
                    'date' => Carbon::now()->toDateString(),
                    'time' => Carbon::now()->toTimeString(),
                    'latitude' => 9.6800,
                    'longitude' => 123.8900,
                    'detailed_location' => 'Sample detailed Location ' . ($i + 1),
                    'more_information' => 'Sample more information for ' . $status . ' ' . ($i + 1),
                    'report_status' => $status,
                    'municipality_id' => 1,
                    'barangay_id' => 1,
                    'is_active' => true,
                    'user_id' => 37,
                ]);

                // Create sighted species for each sighting
                foreach ($sightedSpeciesData as $species) {
                    SightedSpecies::create([
                        'size' => $species['size'],
                        'species_description' => $species['species_description'],
                        'behavior_observed' => $species['behavior_observed'],
                        'species_id' => $species['species_id'],
                        'sighting_id' => $sighting->id,
                    ]);
                }
            }
        }
    }
}
