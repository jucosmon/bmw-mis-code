<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class BarangayMunicipalitySeeder extends Seeder
{
    public function run()
    {
        $municipalities = [
            [
                'name' => 'Panglao',
                'barangays' => [
                    'Bil-isan', 'Bolod', 'Danao', 'Doljo', 'Libaong',
                    'Looc', 'Lourdes', 'Poblacion', 'Tangnan', 'Tawala'
                ]
            ],
            [
                'name' => 'Dauis',
                'barangays' => [
                    'Biking', 'Bingag', 'Catarman', 'Dao', 'Mariveles',
                    'Mayacabac', 'Poblacion', 'San Isidro', 'Songculan',
                    'Tabalong', 'Tinago', 'Totolan'
                ]
            ],
            [
                'name' => 'Tagbilaran',
                'barangays' => [
                    'Bool', 'Booy', 'Cabawan', 'Cogon', 'Dampas', 'Dao',
                    'Manga', 'Mansasa', 'Poblacion 1', 'Poblacion 2',
                    'Poblacion 3', 'San Isidro', 'Taloto', 'Tiptip', 'Ubujan'
                ]
            ],
            [
                'name' => 'Talibon',
                'barangays' => [
                    'Bagacay', 'Balintawak', 'Burgos', 'Busalian', 'Calituban',
                    'Cataban', 'Guindacpan', 'Magsaysay', 'Mahanay', 'Nocnocan',
                    'Poblacion', 'Rizal', 'Sag', 'San Agustin', 'San Carlos',
                    'San Francisco', 'San Isidro', 'San Jose', 'San Pedro',
                    'San Roque', 'Santo Niño', 'Sikatuna', 'Suba', 'Tanghaligue',
                    'Zamora'
                ]
            ],
            [
                'name' => 'Ubay',
                'barangays' => [
                    'Achila', 'Bay-ang', 'Benliw', 'Biabas', 'Bongbong', 'Bood',
                    'Buenavista', 'Bulilis', 'Cagting', 'Calanggaman', 'California',
                    'Camali-an', 'Camambugan', 'Casate', 'Cuya', 'Fatima', 'Gabi',
                    'Governor Boyles', 'Guintabo-an', 'Hambabauran', 'Humayhumay',
                    'Ilihan', 'Imelda', 'Juagdan', 'Katarungan', 'Lomangog',
                    'Los Angeles', 'Pag-asa', 'Pangpang', 'Poblacion', 'San Francisco',
                    'San Isidro', 'San Pascual', 'San Vicente', 'Sentinila',
                    'Sinandigan', 'Tapal', 'Tapon', 'Tintinan', 'Tipolo', 'Tubog',
                    'Tuboran', 'Union', 'Villa Teresita'
                ]
            ],
            [
                'name' => 'Anda',
                'barangays' => [
                    'Almaria', 'Bacong', 'Badiang', 'Buenasuerte', 'Candabong',
                    'Casica', 'Katipunan', 'Linawan', 'Lundag', 'Poblacion',
                    'Santa Cruz', 'Suba', 'Talisay', 'Tanod', 'Tawid', 'Virgen'
                ]
            ],
            [
                'name' => 'Loon',
                'barangays' => [
                    'Agsoso', 'Badbad Occidental', 'Badbad Oriental', 'Bagacay Katipunan',
                    'Bagacay Kawayan', 'Bagacay Saong', 'Bahi', 'Basac', 'Basdacu',
                    'Basdio', 'Biasong', 'Bongco', 'Bugho', 'Cabacongan', 'Cabadug',
                    'Cabug', 'Calayugan Norte', 'Calayugan Sur', 'Cambaquiz', 'Campatud',
                    'Candaigan', 'Canhangdon Occidental', 'Canhangdon Oriental', 'Canigaan',
                    'Canmaag', 'Canmanoc', 'Cansuagwit', 'Cansubayon', 'Cantam-is Bago',
                    'Cantam-is Baslay', 'Cantaongon', 'Cantumocad', 'Catagbacan Handig',
                    'Catagbacan Norte', 'Catagbacan Sur', 'Cogon Norte', 'Cogon Sur',
                    'Cuasi', 'Genomoan', 'Lintuan', 'Looc', 'Mocpoc Norte', 'Mocpoc Sur',
                    'Moto Norte', 'Moto Sur', 'Nagtuang', 'Napo', 'Nueva Vida', 'Panangquilon',
                    'Pantudlan', 'Pig-ot', 'Pondol', 'Quinobcoban', 'Sondol', 'Song-on',
                    'Talisay', 'Tan-awan', 'Tangnan', 'Taytay', 'Ticugan', 'Tiwi', 'Tontonan',
                    'Tubodacu', 'Tubodio', 'Tubuan', 'Ubayon', 'Ubojan'
                ]
            ],
            [
                'name' => 'Clarin',
                'barangays' => [
                    'Bacani', 'Bogtongbod', 'Bonbon', 'Bontud', 'Buacao', 'Buangan',
                    'Cabog', 'Caboy', 'Caluwasan', 'Candajec', 'Cantoyoc', 'Comaang',
                    'Danahao', 'Katipunan', 'Lajog', 'Mataub', 'Nahawan', 'Poblacion Centro',
                    'Poblacion Norte', 'Poblacion Sur', 'Tangaran', 'Tontunan', 'Tubod',
                    'Villaflor'
                ]
            ],
            [
                'name' => 'Tubigon',
                'barangays' => [
                    'Bagongbanwa', 'Banlasan', 'Batasan', 'Bilangbilangan', 'Bosongon',
                    'Buenos Aires', 'Bunacan', 'Cabulijan', 'Cahayag', 'Cawayanan', 'Centro',
                    'Genonocan', 'Guiwanon', 'Ilijan Norte', 'Ilijan Sur', 'Libertad', 'Macaas',
                    'Matabao', 'Mocaboc Island', 'Panadtaran', 'Panaytayon', 'Pandan',
                    'Pangapasan Island', 'Pinayagan Norte', 'Pinayagan Sur', 'Pooc Occidental',
                    'Pooc Oriental', 'Potohan', 'Talenceras', 'Tan-awan', 'Tinangnan', 'Ubojan',
                    'Ubay Island', 'Villanueva'
                ]
            ],
            [
                'name' => 'Jagna',
                'barangays' => [
                    'Alejawan', 'Balili', 'Boctol', 'Bunga Ilaya', 'Bunga Mar', 'Buyog',
                    'Cabunga-an', 'Calabacita', 'Cambugason', 'Can-ipol', 'Canjulao', 'Cantagay',
                    'Cantuyoc', 'Can-uba', 'Can-upao', 'Faraon', 'Ipil', 'Kinagbaan', 'Laca',
                    'Larapan', 'Lonoy', 'Looc', 'Malbog', 'Mayana', 'Naatang', 'Nausok', 'Odiong',
                    'Pagina', 'Pangdan', 'Poblacion', 'Tejero', 'Tubod Mar', 'Tubod Monte'
                ]
            ],
            [
                'name' => 'Alburquerque',
                'barangays' => [
                    'Bahi', 'Basacdacu', 'Cantiguib', 'Dangay', 'East Poblacion', 'Ponong',
                    'San Agustin', 'Santa Filomena', 'Tagbuane', 'Toril', 'West Poblacion'
                ]
            ],
            [
                'name' => 'Baclayon',
                'barangays' => [
                    'Buenaventura', 'Cambanac', 'Dasitam', 'Guiwanon', 'Landican', 'Laya',
                    'Libertad', 'Montana', 'Pamilacan', 'Payahan', 'Poblacion', 'San Isidro',
                    'San Roque', 'San Vicente', 'Santa Cruz', 'Taguihon', 'Tanday'
                ]
            ],
            [
                'name' => 'Bien Unido',
                'barangays' => [
                    'Bilangbilangan Dako', 'Bilangbilangan Diot', 'Hingotanan East', 'Hingotanan West',
                    'Liberty', 'Malingin', 'Mandawa', 'Maomawan', 'Nueva Esperanza', 'Nueva Estrella',
                    'Pinamgo', 'Poblacion', 'Puerto San Pedro', 'Sagasa', 'Tuboran'
                ]
            ],
            [
                'name' => 'Calape',
                'barangays' => [
                    'Abucayan Norte', 'Abucayan Sur', 'Banlasan', 'Bentig', 'Binogawan', 'Bonbon',
                    'Cabayugan', 'Cabudburan', 'Calunasan', 'Camias', 'Canguha', 'Catmonan',
                    'Desamparados', 'Kahayag', 'Kinabag-an', 'Labuon', 'Lawis', 'Liboron', 'Lo-oc',
                    'Lomboy', 'Lucob', 'Madangog', 'Magtongtong', 'Mandaug', 'Mantatao', 'Sampoangon',
                    'San Isidro', 'Santa Cruz', 'Sojoton', 'Talisay', 'Tinibgan', 'Tultugan', 'Ulbujan'
                ]
            ],
            [
                'name' => 'Candijay',
                'barangays' => [
                    'Abihilan', 'Anoling', 'Boyo-an', 'Cadapdapan', 'Cambane', 'Can-Olin', 'Canawa',
                    'Cogtong', 'La Union', 'Luan', 'Lungsuda-an', 'Mahangin', 'Pagahat', 'Panadtaran',
                    'Panas', 'Poblacion', 'San Isidro', 'Tambongan', 'Tawid', 'Tubod', 'Tugas'
                ]
            ],
            [
                'name' => 'Getafe',
                'barangays' => [
                    'Alumar', 'Banacon', 'Buyog', 'Cabasakan', 'Campao Occidental', 'Campao Oriental',
                    'Cangmundo', 'Carlos P. Garcia', 'Corte Baud', 'Handumon', 'Jagoliao', 'Jandayan Norte',
                    'Jandayan Sur', 'Mahanay', 'Nasingin', 'Pandanon', 'Poblacion', 'Saguise', 'Salog',
                    'San Jose', 'Santo Niño', 'Taytay', 'Tugas', 'Tulang'
                ]
            ],
            [
                'name' => 'Guindulman',
                'barangays' => [
                    'Basdio', 'Bato', 'Bayong', 'Biabas', 'Bulawan', 'Cabantian', 'Canhaway', 'Cansiwang',
                    'Casbu', 'Catungawan Norte', 'Catungawan Sur', 'Guinacot', 'Guio-ang', 'Lombog', 'Mayuga',
                    'Sawang', 'Tabajan', 'Tabunok', 'Trinidad'
                ]
            ],
            [
                'name' => 'Inabanga',
                'barangays' => [
                    'Anonang', 'Badiang', 'Baguhan', 'Bahan', 'Banahao', 'Baogo', 'Bugang', 'Cagawasan',
                    'Cagayan', 'Cambitoon', 'Canlinte', 'Cawayan', 'Cogon', 'Cuaming', 'Dagnawan', 'Dagohoy',
                    'Dait Sur', 'Datag', 'Fatima', 'Hambongan', 'Ilaud', 'Ilaya', 'Ilihan', 'Lapacan Norte',
                    'Lapacan Sur', 'Lawis', 'Liloan Norte', 'Liloan Sur', 'Lomboy', 'Lonoy Cainsican', 'Lonoy Roma',
                    'Lutao', 'Luyo', 'Mabuhay', 'Maria Rosario', 'Nabuad', 'Napo', 'Ondol', 'Poblacion', 'Riverside',
                    'Saa', 'San Isidro', 'San Jose', 'Santo Niño', 'Santo Rosario', 'Sua', 'Tambook', 'Tungod', 'U-og',
                    'Ubujan'
                ]
            ],
            [
                'name' => 'Lila',
                'barangays' => [
                    'Banban', 'Bonkokan Ilaya', 'Bonkokan Ubos', 'Calvario', 'Candulang', 'Catugasan', 'Cayupo',
                    'Cogon', 'Jambawan', 'La Fortuna', 'Lomanoy', 'Macalingan', 'Malinao East', 'Malinao West',
                    'Nagsulay', 'Poblacion', 'Taug', 'Tiguis'
                ]
            ],
            [
                'name' => 'Loay',
                'barangays' => [
                    'Agape', 'Alegria Norte', 'Alegria Sur', 'Bonbon', 'Botoc Occidental', 'Botoc Oriental',
                    'Calvario', 'Concepcion', 'Hinawanan', 'Las Salinas Norte', 'Las Salinas Sur', 'Palo',
                    'Poblacion Ibabao', 'Poblacion Ubos', 'Sagnap', 'Tambangan', 'Tangcasan Norte', 'Tangcasan Sur',
                    'Tayong Occidental', 'Tayong Oriental', 'Tocdog Dacu', 'Tocdog Ilaya', 'Villalimpia', 'Yanangan'
                ]
            ],
            [
                'name' => 'Mabini',
                'barangays' => [
                    'Abaca', 'Abad Santos', 'Aguipo', 'Baybayon', 'Bulawan', 'Cabidian', 'Cawayanan', 'Concepcion',
                    'Del Mar', 'Lungsoda-an', 'Marcelo', 'Minol', 'Paraiso', 'Poblacion 1', 'Poblacion 2', 'San Isidro',
                    'San Jose', 'San Rafael', 'San Roque', 'Tambo', 'Tangkigan', 'Valaga'
                ]
            ],
            [
                'name' => 'Maribojoc',
                'barangays' => [
                    'Agahay', 'Aliguay', 'Anislag', 'Bayacabac', 'Bood', 'Busao', 'Cabawan', 'Candavid', 'Dipatlong',
                    'Guiwanon', 'Jandig', 'Lagtangon', 'Lincod', 'Pagnitoan', 'Poblacion', 'Punsod', 'Punta Cruz',
                    'San Isidro', 'San Roque', 'San Vicente', 'Tinibgan', 'Toril'
                ]
            ],
            [
                'name' => 'Valencia',
                'barangays' => [
                    'Adlawan', 'Anas', 'Anonang', 'Anoyon', 'Balingasao', 'Banderahan', 'Botong', 'Buyog',
                    'Canduao Occidental', 'Canduao Oriental', 'Canlusong', 'Canmanico', 'Cansibao', 'Catug-a',
                    'Cutcutan', 'Danao', 'Genoveva', 'Ginopolan', 'La Victoria', 'Lantang', 'Limocon', 'Loctob',
                    'Magsaysay', 'Marawis', 'Maubo', 'Nailo', 'Omjon', 'Pangi-an', 'Poblacion Occidental',
                    'Poblacion Oriental', 'Simang', 'Taug', 'Tausion', 'Taytay', 'Ticum'
                ]
            ],
            [
                'name' => 'Buenavista',
                'barangays' => [
                    'Anonang', 'Asinan', 'Bago', 'Baluarte', 'Bantuan', 'Bato', 'Bonotbonot', 'Bugaong',
                    'Cambuhat', 'Cambus-oc', 'Cangawa', 'Cantomugcad', 'Cantores', 'Cantuba', 'Catigbian',
                    'Cawag', 'Cruz', 'Dait', 'Eastern Cabul-an', 'Hunan', 'Lapacan Norte', 'Lapacan Sur',
                    'Lubang', 'Lusong', 'Magkaya', 'Merryland', 'Nueva Granada', 'Nueva Montana', 'Overland',
                    'Panghagban', 'Poblacion', 'Puting Bato', 'Rufo Hill', 'Sweetland', 'Western Cabul-an'
                ]
            ],
            [
                'name' => 'Cortes',
                'barangays' => [
                    'De la Paz', 'Fatima', 'Loreto', 'Lourdes', 'Malayo Norte', 'Malayo Sur', 'Monserrat',
                    'New Lourdes', 'Patrocinio', 'Poblacion', 'Rosario', 'Salvador', 'San Roque', 'Upper de la Paz'
                ]
            ],
            [
                'name' => 'Dimiao',
                'barangays' => [
                    'Abihid', 'Alemania', 'Baguhan', 'Bakilid', 'Balbalan', 'Banban', 'Bauhugan', 'Bilisan',
                    'Cabagakian', 'Cabanbanan', 'Cadap-agan', 'Cambacol', 'Cambayaon', 'Canhayupon', 'Canlambong',
                    'Casingan', 'Catugasan', 'Datag', 'Guindaguitan', 'Guingoyuran', 'Ile', 'Lapsaon', 'Limokon Ilaod',
                    'Limokon Ilaya', 'Luyo', 'Malijao', 'Oac', 'Pagsa', 'Pangihawan', 'Puangyuta', 'Sawang', 'Tangohay',
                    'Taongon Cabatuan', 'Taongon Can-andam', 'Tawid Bitaog'
                ]
            ],
            [
                'name' => 'Duero',
                'barangays' => [
                    'Alejawan', 'Angilan', 'Anibongan', 'Bangwalog', 'Cansuhay', 'Danao', 'Duay', 'Guinsularan',
                    'Imelda', 'Itum', 'Langkis', 'Lobogon', 'Madua Norte', 'Madua Sur', 'Mambool', 'Mawi', 'Payao',
                    'San Antonio', 'San Isidro', 'San Pedro', 'Taytay'
                ]
            ],
            [
                'name' => 'Garcia Hernandez',
                'barangays' => [
                    'Abijilan', 'Antipolo', 'Basiao', 'Cagwang', 'Calma', 'Cambuyo', 'Canayaon East', 'Canayaon West',
                    'Candanas', 'Candulao', 'Catmon', 'Cayam', 'Cupa', 'Datag', 'Estaca', 'Libertad', 'Lungsodaan East',
                    'Lungsodaan West', 'Malinao', 'Manaba', 'Pasong', 'Poblacion East', 'Poblacion West', 'Sacaon',
                    'Sampong', 'Tabuan', 'Togbongon', 'Ulbujan East', 'Ulbujan West', 'Victoria'
                ]
            ],
            [
                'name' => 'President Carlos P. Garcia',
                'barangays' => [
                    'Aguining', 'Basiao', 'Baud', 'Bayog', 'Bogo', 'Bonbonon', 'Butan', 'Campamanog', 'Canmangao',
                    'Gaus', 'Kabangkalan', 'Lapinig', 'Lipata', 'Poblacion', 'Popoo', 'Saguise', 'San Jose', 'San Vicente',
                    'Santo Rosario', 'Tilmobo', 'Tugas', 'Tugnao', 'Villa Milagrosa'
                ]
            ]
        ];

        // Insert municipalities and barangays
        foreach ($municipalities as $municipality) {
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
