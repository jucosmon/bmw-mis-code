<?php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors = [
            'Red',
            'Green',
            'Blue',
            'Yellow',
            'Orange',
            'Purple',
            'Pink',
            'Brown',
            'Black',
            'White',
            'Gray',
            'Cyan',
            'Magenta',
            'Lime',
            'Teal',
            'Navy',
            'Maroon',
            'Olive',
            'Coral',
            'Gold',
            'Silver',
        ];

        foreach ($colors as $color) {
            Color::firstOrCreate(['name' => $color]);
        }
    }
}
