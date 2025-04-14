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
        // Bohol municipalities with coastal areas
        $municipalities = [
            1 => 'Tagbilaran City',
            2 => 'Panglao',
            3 => 'Dauis',
            4 => 'Baclayon',
            5 => 'Alburquerque',
            6 => 'Loay',
            7 => 'Lila',
            8 => 'Dimiao',
            9 => 'Valencia',
            10 => 'Garcia Hernandez',
            11 => 'Jagna',
            12 => 'Duero',
            13 => 'Guindulman',
            14 => 'Anda',
            15 => 'Candijay',
        ];

        // Representative barangays for coastal areas (simplified)
        $barangays = [
            1 => 'Poblacion',
            2 => 'Tawala',
            3 => 'Danao',
            4 => 'Catarman',
            5 => 'Tagbuane',
            6 => 'Tayong',
            7 => 'Bonkokan',
            8 => 'Banban',
            9 => 'Cansiwang',
            10 => 'Lungsodaan',
        ];

        // Marine species common in Bohol Sea
        $speciesData = [
            1 => [
                'name' => 'Green Sea Turtle (Chelonia mydas)',
                'sizes' => ['small', 'medium', 'large'],
                'descriptions' => [
                    'Adult green turtle with distinct carapace pattern',
                    'Juvenile green turtle with bright coloration',
                    'Massive adult green turtle estimated over 100kg'
                ],
                'behaviors' => [
                    'Swimming near the surface',
                    'Feeding on seagrass',
                    'Resting on coral reef',
                    'Coming up for air'
                ]
            ],
            2 => [
                'name' => 'Hawksbill Turtle (Eretmochelys imbricata)',
                'sizes' => ['small', 'medium'],
                'descriptions' => [
                    'Distinctive hawk-like beak visible',
                    'Beautiful patterned shell with serrated edges',
                    'Small hawksbill with amber coloration'
                ],
                'behaviors' => [
                    'Foraging among coral',
                    'Swimming along reef edge',
                    'Surfacing briefly for air',
                    'Diving to deeper water'
                ]
            ],
            3 => [
                'name' => 'Spinner Dolphin (Stenella longirostris)',
                'sizes' => ['medium', 'large'],
                'descriptions' => [
                    'Pod of spinner dolphins with distinctive markings',
                    'Adult spinner with characteristic dorsal fin',
                    'Mother and calf pair swimming in synchrony'
                ],
                'behaviors' => [
                    'Leaping and spinning out of water',
                    'Bow riding in front of boat',
                    'Traveling in large pod',
                    'Hunting in coordinated group'
                ]
            ],
            4 => [
                'name' => 'Dugong (Dugong dugon)',
                'sizes' => ['large', 'very large'],
                'descriptions' => [
                    'Solitary adult dugong with visible scars',
                    'Large dugong with pale coloration',
                    'Massive specimen estimated over 400kg'
                ],
                'behaviors' => [
                    'Grazing slowly on seagrass beds',
                    'Surfacing for air',
                    'Swimming near mangrove areas',
                    'Resting in shallow water'
                ]
            ],
            5 => [
                'name' => 'Whale Shark (Rhincodon typus)',
                'sizes' => ['large', 'very large'],
                'descriptions' => [
                    'Enormous whale shark with distinctive spot pattern',
                    'Juvenile whale shark approximately 4 meters long',
                    'Massive adult whale shark with remoras attached'
                ],
                'behaviors' => [
                    'Filter feeding near surface',
                    'Slow swimming with mouth open',
                    'Vertical feeding in plankton-rich water',
                    'Cruising parallel to shoreline'
                ]
            ],
            6 => [
                'name' => 'Blacktip Reef Shark (Carcharhinus melanopterus)',
                'sizes' => ['small', 'medium'],
                'descriptions' => [
                    'Distinctive black-tipped fins clearly visible',
                    'Slender reef shark patrolling shallow area',
                    'Approximately 1.5m shark with streamlined body'
                ],
                'behaviors' => [
                    'Patrolling reef edge',
                    'Quick darting movements in shallows',
                    'Circling bait fish school',
                    'Resting on sandy bottom'
                ]
            ],
            7 => [
                'name' => 'Manta Ray (Mobula birostris)',
                'sizes' => ['large', 'very large'],
                'descriptions' => [
                    'Enormous manta ray with wingspan over 4 meters',
                    'Distinctive markings on ventral surface',
                    'Graceful manta with remoras attached underneath'
                ],
                'behaviors' => [
                    'Gliding effortlessly through water column',
                    'Barrel rolling while feeding',
                    'Breaching completely out of water',
                    'Filter feeding with cephalic fins extended'
                ]
            ],
            8 => [
                'name' => 'Bottlenose Dolphin (Tursiops truncatus)',
                'sizes' => ['medium', 'large'],
                'descriptions' => [
                    'Pod of bottlenose dolphins with various sizes',
                    'Large male with scarred dorsal fin',
                    'Mother and calf pair surfacing together'
                ],
                'behaviors' => [
                    'Playful jumping near boat',
                    'Socializing in small group',
                    'Foraging in coordinated pattern',
                    'Vocalizing audibly at surface'
                ]
            ],
            9 => [
                'name' => 'False Killer Whale (Pseudorca crassidens)',
                'sizes' => ['large', 'very large'],
                'descriptions' => [
                    'Small pod of false killer whales',
                    'Large adult with characteristic sloping forehead',
                    'Dark-colored cetacean moving quickly through water'
                ],
                'behaviors' => [
                    'Fast directional swimming',
                    'Synchronous diving as a group',
                    'Hunting behavior observed',
                    'Surface active behavior with splashing'
                ]
            ],
            10 => [
                'name' => 'Humphead Wrasse (Cheilinus undulatus)',
                'sizes' => ['medium', 'large'],
                'descriptions' => [
                    'Enormous humphead wrasse with brilliant coloration',
                    'Distinctive bump on head clearly visible',
                    'Massive reef fish observed near coral formation'
                ],
                'behaviors' => [
                    'Slowly cruising reef area',
                    'Feeding on shellfish',
                    'Resting in reef crevice',
                    'Following divers curiously'
                ]
            ],
        ];

        // Sample data for 20 sightings with randomness
        $sightingsData = [
            [
                'certainty_level' => 5,
                'date' => '2013-04-17',
                'time' => '09:45:00',
                'latitude' => 9.6214,
                'longitude' => 123.7758,
                'detailed_location' => 'Alona Beach, Panglao Island',
                'more_information' => 'Spotted during morning snorkeling trip, water visibility excellent',
                'report_status' => 'verified',
                'municipality_id' => 2,
                'barangay_id' => 2,
                'species_id' => 1
            ],
            [
                'certainty_level' => 4,
                'date' => '2014-08-23',
                'time' => '16:20:00',
                'latitude' => 9.5839,
                'longitude' => 123.8656,
                'detailed_location' => 'Balicasag Island Marine Sanctuary',
                'more_information' => 'Observed by diving group at depth of about 15 meters',
                'report_status' => 'verified',
                'municipality_id' => 2,
                'barangay_id' => 2,
                'species_id' => 2
            ],
            [
                'certainty_level' => 3,
                'date' => '2015-03-12',
                'time' => '07:30:00',
                'latitude' => 9.8506,
                'longitude' => 124.1439,
                'detailed_location' => 'Off coast of Jagna',
                'more_information' => 'Seen from fishing boat during early morning',
                'report_status' => 'pending',
                'municipality_id' => 11,
                'barangay_id' => 1,
                'species_id' => 3
            ],
            [
                'certainty_level' => 5,
                'date' => '2016-01-05',
                'time' => '14:15:00',
                'latitude' => 9.7753,
                'longitude' => 124.0144,
                'detailed_location' => 'Seagrass beds near Mabini',
                'more_information' => 'Rare dugong sighting, photographed by local conservation team',
                'report_status' => 'verified',
                'municipality_id' => 10,
                'barangay_id' => 5,
                'species_id' => 4
            ],
            [
                'certainty_level' => 5,
                'date' => '2017-05-19',
                'time' => '10:40:00',
                'latitude' => 9.6139,
                'longitude' => 123.9528,
                'detailed_location' => 'Pamilacan Island waters',
                'more_information' => 'Multiple tour boats observed feeding activity',
                'report_status' => 'verified',
                'municipality_id' => 4,
                'barangay_id' => 6,
                'species_id' => 5
            ],
            [
                'certainty_level' => 3,
                'date' => '2018-07-02',
                'time' => '11:05:00',
                'latitude' => 9.7014,
                'longitude' => 123.9011,
                'detailed_location' => 'Shallow reef area near Baclayon',
                'more_information' => 'Fisherman reported shark in area, species uncertain',
                'report_status' => 'pending',
                'municipality_id' => 4,
                'barangay_id' => 4,
                'species_id' => 6
            ],
            [
                'certainty_level' => 4,
                'date' => '2019-02-08',
                'time' => '15:30:00',
                'latitude' => 9.5517,
                'longitude' => 123.8042,
                'detailed_location' => 'Doljo Point, Panglao',
                'more_information' => 'Seen during afternoon dive, manta approached divers',
                'report_status' => 'verified',
                'municipality_id' => 2,
                'barangay_id' => 3,
                'species_id' => 7
            ],
            [
                'certainty_level' => 5,
                'date' => '2019-12-16',
                'time' => '08:10:00',
                'latitude' => 9.7583,
                'longitude' => 123.5250,
                'detailed_location' => 'Cabilao Island, western side',
                'more_information' => 'Pod estimated at 20-25 individuals, playful behavior',
                'report_status' => 'verified',
                'municipality_id' => 14,
                'barangay_id' => 1,
                'species_id' => 8
            ],
            [
                'certainty_level' => 2,
                'date' => '2020-09-30',
                'time' => '17:40:00',
                'latitude' => 9.9175,
                'longitude' => 124.1753,
                'detailed_location' => 'Offshore near Duero',
                'more_information' => 'Brief sighting at sunset, identity not confirmed',
                'report_status' => 'pending',
                'municipality_id' => 12,
                'barangay_id' => 9,
                'species_id' => 9
            ],
            [
                'certainty_level' => 4,
                'date' => '2021-04-22',
                'time' => '09:25:00',
                'latitude' => 9.6197,
                'longitude' => 123.9542,
                'detailed_location' => 'Reef drop-off at Pamilacan',
                'more_information' => 'Rare sighting of large humphead wrasse during survey dive',
                'report_status' => 'verified',
                'municipality_id' => 4,
                'barangay_id' => 6,
                'species_id' => 10
            ],
            [
                'certainty_level' => 5,
                'date' => '2021-11-07',
                'time' => '14:00:00',
                'latitude' => 9.6350,
                'longitude' => 123.8631,
                'detailed_location' => 'Balicasag Island, Black Forest dive site',
                'more_information' => 'Multiple turtles sighted during conservation monitoring',
                'report_status' => 'verified',
                'municipality_id' => 2,
                'barangay_id' => 2,
                'species_id' => 1
            ],
            [
                'certainty_level' => 3,
                'date' => '2022-02-14',
                'time' => '10:30:00',
                'latitude' => 9.8122,
                'longitude' => 124.0256,
                'detailed_location' => 'Between Lila and Dimiao',
                'more_information' => 'Brief sighting from tour boat, photos inconclusive',
                'report_status' => 'pending',
                'municipality_id' => 7,
                'barangay_id' => 7,
                'species_id' => 3
            ],
            [
                'certainty_level' => 1,
                'date' => '2022-06-19',
                'time' => '06:15:00',
                'latitude' => 9.8753,
                'longitude' => 124.0825,
                'detailed_location' => 'Offshore from Valencia',
                'more_information' => 'Reported by fisherman, low visibility conditions',
                'report_status' => 'false',
                'municipality_id' => 9,
                'barangay_id' => 9,
                'species_id' => 7
            ],
            [
                'certainty_level' => 5,
                'date' => '2023-01-03',
                'time' => '11:20:00',
                'latitude' => 9.5697,
                'longitude' => 123.7903,
                'detailed_location' => 'Marine sanctuary near Danao Beach',
                'more_information' => 'Photographed by marine biology students during field trip',
                'report_status' => 'verified',
                'municipality_id' => 2,
                'barangay_id' => 3,
                'species_id' => 6
            ],
            [
                'certainty_level' => 4,
                'date' => '2023-07-25',
                'time' => '09:50:00',
                'latitude' => 9.6428,
                'longitude' => 123.8558,
                'detailed_location' => 'Balicasag Island, eastern side',
                'more_information' => 'Observed during regular monitoring activity',
                'report_status' => 'verified',
                'municipality_id' => 2,
                'barangay_id' => 2,
                'species_id' => 2
            ],
            [
                'certainty_level' => 5,
                'date' => '2023-10-12',
                'time' => '15:00:00',
                'latitude' => 9.6511,
                'longitude' => 123.8522,
                'detailed_location' => 'Anda Peninsula, Quinale Beach area',
                'more_information' => 'Large individual spotted by community patrol team',
                'report_status' => 'verified',
                'municipality_id' => 14,
                'barangay_id' => 1,
                'species_id' => 5
            ],
            [
                'certainty_level' => 2,
                'date' => '2024-01-30',
                'time' => '07:45:00',
                'latitude' => 9.7069,
                'longitude' => 123.9139,
                'detailed_location' => 'Off coast of Baclayon',
                'more_information' => 'Brief sighting, species identification uncertain',
                'report_status' => 'pending',
                'municipality_id' => 4,
                'barangay_id' => 4,
                'species_id' => 9
            ],
            [
                'certainty_level' => 5,
                'date' => '2024-03-18',
                'time' => '13:10:00',
                'latitude' => 9.8289,
                'longitude' => 124.0739,
                'detailed_location' => 'Candijay mangrove reserve',
                'more_information' => 'Dugong feeding in seagrass, observed for over 30 minutes',
                'report_status' => 'verified',
                'municipality_id' => 15,
                'barangay_id' => 10,
                'species_id' => 4
            ],
            [
                'certainty_level' => 4,
                'date' => '2024-08-09',
                'time' => '16:45:00',
                'latitude' => 9.6033,
                'longitude' => 123.8944,
                'detailed_location' => 'Between Panglao and Pamilacan Island',
                'more_information' => 'Large pod observed from tourist boat, very active',
                'report_status' => 'verified',
                'municipality_id' => 2,
                'barangay_id' => 2,
                'species_id' => 8
            ],
            [
                'certainty_level' => 3,
                'date' => '2024-10-05',
                'time' => '07:30:00',
                'latitude' => 9.5947,
                'longitude' => 123.7831,
                'detailed_location' => 'Panglao Bay, near Hinagdanan area',
                'more_information' => 'Individual observed during early morning kayaking trip',
                'report_status' => 'pending',
                'municipality_id' => 3,
                'barangay_id' => 3,
                'species_id' => 1
            ],
        ];

        // Create each sighting with its associated species observation
        foreach ($sightingsData as $sightingData) {
            $speciesId = $sightingData['species_id'];
            unset($sightingData['species_id']);

            // Set user_id and is_active fields
            $sightingData['user_id'] = rand(1, 40); // Assuming user IDs range from 1 to 40
            $sightingData['is_active'] = true;

            // Create the sighting
            $sighting = Sighting::create($sightingData);

            // Get the species data
            $species = $speciesData[$speciesId];

            // Create sighted species entry
            SightedSpecies::create([
                'size' => $species['sizes'][array_rand($species['sizes'])],
                'species_description' => $species['descriptions'][array_rand($species['descriptions'])],
                'behavior_observed' => $species['behaviors'][array_rand($species['behaviors'])],
                'species_id' => $speciesId,
                'sighting_id' => $sighting->id,
            ]);

            // Sometimes add additional species sighting (30% chance)
            if (rand(1, 100) <= 30) {
                // Get a different random species
                $additionalSpeciesId = $speciesId;
                while ($additionalSpeciesId == $speciesId) {
                    $additionalSpeciesId = rand(1, count($speciesData));
                }

                $additionalSpecies = $speciesData[$additionalSpeciesId];

                SightedSpecies::create([
                    'size' => $additionalSpecies['sizes'][array_rand($additionalSpecies['sizes'])],
                    'species_description' => $additionalSpecies['descriptions'][array_rand($additionalSpecies['descriptions'])],
                    'behavior_observed' => $additionalSpecies['behaviors'][array_rand($additionalSpecies['behaviors'])],
                    'species_id' => $additionalSpeciesId,
                    'sighting_id' => $sighting->id,
                ]);
            }
        }
    }
}
