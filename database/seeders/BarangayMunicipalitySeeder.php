<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class BarangayMunicipalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $municipalities = [
            [
                'name' => 'Panglao',
                'barangays' => [
                    'Bil-isan',
                    'Danao',
                    'Doljo',
                    'Looc',
                    'Lourdes',
                    'Poblacion',
                    'Tangnan',
                    'Tawala',
                ],
            ],
            [
                'name' => 'Dauis',
                'barangays' => [
                    'Biking',
                    'Bingag',
                    'Catarman',
                    'Dao',
                    'Mayacabac',
                    'Poblacion',
                    'San Isidro',
                    'Songculan',
                    'Tabalong',
                    'Tinago',
                ],
            ],
            [
                'name' => 'Tagbilaran City',
                'barangays' => [
                    'Bool',
                    'Booy',
                    'Cogon',
                    'Dao',
                    'Mansasa',
                    'Poblacion I',
                    'Poblacion II',
                    'Poblacion III',
                    'San Isidro',
                    'Taloto',
                    'Tiptip',
                    'Ubujan',
                ],
            ],
            [
                'name' => 'Talibon',
                'barangays' => [
                    'Balintawak',
                    'Bagacay',
                    'Busalian',
                    'Cataban',
                    'San Carlos',
                    'Suba',
                    'Tugas',
                    'Zamora',
                ],
            ],
            [
                'name' => 'Ubay',
                'barangays' => [
                    'Bongbong',
                    'Fatima',
                    'Poblacion',
                    'San Pascual',
                    'San Vicente',
                    'Tapal',
                    'Tapon',
                ],
            ],
            [
                'name' => 'Anda',
                'barangays' => [
                    'Bacong',
                    'Badiang',
                    'Candabong',
                    'Casica',
                    'Linawan',
                    'Poblacion',
                    'Santa Cruz',
                    'Suba',
                    'Talisay',
                ],
            ],
            [
                'name' => 'Loon',
                'barangays' => [
                    'Basdacu',
                    'Basdio',
                    'Canhangdon',
                    'Napo',
                    'Poblacion',
                    'Tangnan',
                    'Tubodacu',
                    'Tubodio',
                    'Tontonan',
                ],
            ],
            [
                'name' => 'Clarin',
                'barangays' => [
                    'Bacani',
                    'Bogtongbod',
                    'Bonbon',
                    'Nahawan',
                    'Poblacion Norte',
                    'Poblacion Sur',
                    'Tontonan',
                    'Tubigon',
                ],
            ],
            [
                'name' => 'Tubigon',
                'barangays' => [
                    'Batasan',
                    'Buli',
                    'Centro',
                    'Guiwanon',
                    'Macaas',
                    'Matabao',
                    'Pooc Occidental',
                    'Pooc Oriental',
                    'Tinangnan',
                ],
            ],
            [
                'name' => 'Jagna',
                'barangays' => [
                    'Alejawan',
                    'Bunga Ilaya',
                    'Can-uba',
                    'Canjulao',
                    'Cantagay',
                    'Ipil',
                    'Larapan',
                    'Laca',
                    'Looc',
                    'Tubod Mar',
                ],
            ],
        ];

        // Insert municipalities and barangays
        foreach ($municipalities as $municipality) {
            // Insert into municipalities table and get the ID
            $municipalityId = DB::table('municipalities')->insertGetId([
                'name' => $municipality['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert related barangays
            foreach ($municipality['barangays'] as $barangay) {
                DB::table('barangays')->insert([
                    'name' => $barangay,
                    'municipality_id' => $municipalityId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
