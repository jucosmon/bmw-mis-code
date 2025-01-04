<?php

namespace Database\Seeders;

use App\Models\StrandedIncident;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class StrandedIncidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {

        // Pending
        StrandedIncident::create([
            'certainty_level' => 3,
            'date' => Carbon::now()->toDateString(),
            'time' => Carbon::now()->toTimeString(),
            'species_involved' => 'Whale',
            'quantity' => 1,
            'condition' => 'alive',
            'latitude' => 9.6615,
            'longitude' => 123.8797,
            'sea_state' => 'calm',
            'weather' => 'sunny',
            'beach_type' => 'sandy',
            'detailed_location' => 'Near the coast of Panglao',
            'more_information' => 'The whale seems to be stranded and in distress.',
            'report_status' => 'pending',
            'is_false' => false,
            'is_active' => true,
            'municipality_id' => 1, // Change as per your created user
            'barangay_id' => 1,     // Change as per your created user
            'user_id' => 36,         // Change as per your created user
        ]);

        StrandedIncident::create([
            'certainty_level' => 2,
            'date' => Carbon::now()->toDateString(),
            'time' => Carbon::now()->toTimeString(),
            'species_involved' => 'Turtle',
            'quantity' => 3,
            'condition' => 'alive',
            'latitude' => 9.6598,
            'longitude' => 123.8767,
            'sea_state' => 'rough',
            'weather' => 'cloudy',
            'beach_type' => 'rocky',
            'detailed_location' => 'Near the baybay of chuchu',
            'more_information' => 'A group of turtles seems to be disoriented.',
            'report_status' => 'pending',
            'is_false' => false,
            'is_active' => true,
            'municipality_id' => 1, // Change as per your created user
            'barangay_id' => 1,     // Change as per your created user
            'user_id' => 37,         // Change as per your created user
        ]);

        // Verified
        StrandedIncident::create([
            'certainty_level' => 4,
            'date' => Carbon::now()->toDateString(),
            'time' => Carbon::now()->toTimeString(),
            'species_involved' => 'Dolphin',
            'quantity' => 2,
            'condition' => 'alive',
            'latitude' => 9.6703,
            'longitude' => 123.8770,
            'sea_state' => 'calm',
            'weather' => 'sunny',
            'beach_type' => 'sandy',
            'detailed_location' => 'Alona Beach, Panglao',
            'more_information' => 'Dolphins are confirmed stranded.',
            'report_status' => 'verified',
            'is_false' => false,
            'is_active' => true,
            'municipality_id' => 1, // Change as per your created user
            'barangay_id' => 1,     // Change as per your created user
            'user_id' => 36,         // Change as per your created user
        ]);

        StrandedIncident::create([
            'certainty_level' => 5,
            'date' => Carbon::now()->toDateString(),
            'time' => Carbon::now()->toTimeString(),
            'species_involved' => 'Shark',
            'quantity' => 1,
            'condition' => 'dead',
            'latitude' => 9.6500,
            'longitude' => 123.8800,
            'sea_state' => 'rough',
            'weather' => 'rainy',
            'beach_type' => 'reef',
            'detailed_location' => 'Danao Beach, Panglao',
            'more_information' => 'Shark found dead on the beach.',
            'report_status' => 'verified',
            'is_false' => false,
            'is_active' => true,
            'municipality_id' => 2, // Change as per your created user
            'barangay_id' => 9,     // Change as per your created user
            'user_id' => 38,         // Change as per your created user
        ]);

        // Completed
        StrandedIncident::create([
            'certainty_level' => 3,
            'date' => Carbon::now()->toDateString(),
            'time' => Carbon::now()->toTimeString(),
            'species_involved' => 'Turtle',
            'quantity' => 2,
            'condition' => 'alive',
            'latitude' => 9.6880,
            'longitude' => 123.8835,
            'sea_state' => 'calm',
            'weather' => 'sunny',
            'beach_type' => 'sandy',
            'detailed_location' => 'Anda, Bohol',
            'more_information' => 'The turtles have been safely returned to the sea.',
            'report_status' => 'completed',
            'is_false' => false,
            'is_active' => true,
            'municipality_id' => 1, // Change as per your created user
            'barangay_id' => 1,     // Change as per your created user
            'user_id' => 39,         // Change as per your created user
        ]);

        StrandedIncident::create([
            'certainty_level' => 1,
            'date' => Carbon::now()->toDateString(),
            'time' => Carbon::now()->toTimeString(),
            'species_involved' => 'Fish',
            'quantity' => 10,
            'condition' => 'alive',
            'latitude' => 9.6900,
            'longitude' => 123.8805,
            'sea_state' => 'rough',
            'weather' => 'cloudy',
            'beach_type' => 'reef',
            'detailed_location' => 'Bohol Beach Club, Panglao',
            'more_information' => 'Fish sighted near the shore.',
            'report_status' => 'completed',
            'is_false' => false,
            'is_active' => true,
            'municipality_id' => 2, // Change as per your created user
            'barangay_id' => 10,     // Change as per your created user
            'user_id' => 37,         // Change as per your created user
        ]);

        // Resolved
        StrandedIncident::create([
            'certainty_level' => 4,
            'date' => Carbon::now()->toDateString(),
            'time' => Carbon::now()->toTimeString(),
            'species_involved' => 'Dolphin',
            'quantity' => 1,
            'condition' => 'dead',
            'latitude' => 9.6800,
            'longitude' => 123.8900,
            'sea_state' => 'calm',
            'weather' => 'sunny',
            'beach_type' => 'sandy',
            'detailed_location' => 'Anda, Bohol',
            'more_information' => 'Dolphin found dead, resolved by authorities.',
            'report_status' => 'resolved',
            'is_false' => false,
            'is_active' => true,
            'municipality_id' => 1, // Change as per your created user
            'barangay_id' => 1,     // Change as per your created user
            'user_id' => 36,         // Change as per your created user
        ]);

        StrandedIncident::create([
            'certainty_level' => 3,
            'date' => Carbon::now()->toDateString(),
            'time' => Carbon::now()->toTimeString(),
            'species_involved' => 'Whale Shark',
            'quantity' => 1,
            'condition' => 'alive',
            'latitude' => 9.6905,
            'longitude' => 123.8950,
            'sea_state' => 'calm',
            'weather' => 'sunny',
            'beach_type' => 'sandy',
            'detailed_location' => 'Panglao Beach',
            'more_information' => 'The whale shark was safely returned to the sea.',
            'report_status' => 'resolved',
            'is_false' => false,
            'is_active' => true,
            'municipality_id' => 3, // Change as per your created user
            'barangay_id' => 19,     // Change as per your created user
            'user_id' => 40,         // Change as per your created user
        ]);

        // False
        StrandedIncident::create([
            'certainty_level' => 1,
            'date' => Carbon::now()->toDateString(),
            'time' => Carbon::now()->toTimeString(),
            'species_involved' => 'Fish',
            'quantity' => 5,
            'condition' => 'alive',
            'latitude' => 9.7000,
            'longitude' => 123.8990,
            'sea_state' => 'calm',
            'weather' => 'sunny',
            'beach_type' => 'sandy',
            'detailed_location' => 'Alona Beach',
            'more_information' => 'The sighting was confirmed to be a false report.',
            'report_status' => 'false',
            'is_false' => true,
            'is_active' => false,
            'municipality_id' => 2, // Change as per your created user
            'barangay_id' => 9,     // Change as per your created user
            'user_id' => 39,         // Change as per your created user
        ]);

        StrandedIncident::create([
            'certainty_level' => 2,
            'date' => Carbon::now()->toDateString(),
            'time' => Carbon::now()->toTimeString(),
            'species_involved' => 'Sea Turtle',
            'quantity' => 1,
            'condition' => 'alive',
            'latitude' => 9.7100,
            'longitude' => 123.9000,
            'sea_state' => 'rough',
            'weather' => 'rainy',
            'beach_type' => 'rocky',
            'detailed_location' => 'Tawala Beach',
            'more_information' => 'The report was a false alarm.',
            'report_status' => 'false',
            'is_false' => true,
            'is_active' => false,
            'municipality_id' => 1, // Change as per your created user
            'barangay_id' => 1,     // Change as per your created user
            'user_id' => 41,        // Change as per your created user
        ]);
    }

}
