<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $municipalities = [1, 2, 3, 4, 5];
        $barangays = [
            [1, 2, 3, 4, 5],
            [6, 7, 8, 9, 10],
            [11, 12, 13, 14, 15],
            [16, 17, 18, 19, 20],
            [21, 22, 23, 24, 25],
        ];

        // Creating 2 BPEMO Admin users
        User::create([
            'first_name' => 'BPEMO Admin',
            'last_name' => 'One',
            'email' => 'bpemoadmin1@example.com',
            'password' => Hash::make(value: 'password123'),
            'user_role' => 'bpemo_admin',
            'contact_number' => '091234567890',
            'birthdate'=> '1980/01/01',
            'position'=>'BPEMO Head',
            'sex' => 'female',
            'municipality_id' => null,
            'barangay_id' => null,
        ]);

        User::create([
            'first_name' => 'BPEMO Admin',
            'last_name' => 'Two',
            'email' => 'bpemoadmin2@example.com',
            'password' => Hash::make('password123'),
            'user_role' => 'bpemo_admin',
            'contact_number' => '091234567891',
            'birthdate'=> '1985/02/01',
            'position'=>'BPEMO Marine Head',
            'sex' => 'female',
            'municipality_id' => null,
            'barangay_id' => null,
        ]);

        // Creating 2 BPEMO Staff users
        User::create([
            'first_name' => 'BPEMO Staff',
            'last_name' => 'One',
            'email' => 'bpemostaff1@example.com',
            'password' => Hash::make('password123'),
            'user_role' => 'bpemo_staff',
            'contact_number' => '091234567892',
            'birthdate'=> '1990/03/01',
            'position'=>'Marine Staff',
            'sex' => 'male',
            'municipality_id' => null,
            'barangay_id' => null,
        ]);

        User::create([
            'first_name' => 'Staff',
            'last_name' => 'Two',
            'email' => 'staff2@example.com',
            'password' => Hash::make('password123'),
            'user_role' => 'bpemo_staff',
            'contact_number' => '091234567893',
            'birthdate'=> '1992/04/01',
            'position'=>'Marine Staff',
            'sex' => 'female',
            'municipality_id' => null,
            'barangay_id' => null,
        ]);

        User::create([
            'first_name' => 'Staff',
            'last_name' => 'Three',
            'email' => 'staff3@example.com',
            'password' => Hash::make('password123'),
            'user_role' => 'bpemo_staff',
            'contact_number' => '091234567893',
            'birthdate'=> '1992/04/01',
            'position'=>'Marine Secretary',
            'sex' => 'male',
            'municipality_id' => null,
            'barangay_id' => null,
        ]);

        // Creating 5 LGU Responders with different municipality IDs
        for ($i = 0; $i < 5; $i++) {
            $lguResponder = User::create([
                'first_name' => 'LGU',
                'last_name' => 'Responder ' . ($i + 1),
                'email' => 'lguresponder' . ($i + 1) . '@example.com',
                'password' => Hash::make('password123'),
                'user_role' => 'lgu_responder',
                'contact_number' => '0912345678' . rand(1000, 9999),
                'birthdate'=> '1995/05/01',
                'position'=>'LGU Staff',
                'sex' => $i % 2 == 0 ? 'male' : 'female',
                'municipality_id' => $municipalities[$i],
                'barangay_id' => null,
            ]);

            // Creating 5 Barangay Officials for each LGU Responder
            foreach ($barangays[$i] as $barangay_id) {
                User::create([
                    'first_name' => 'Barangay',
                    'last_name' => 'Official ' . ($barangay_id - $municipalities[$i] * 5), // A unique name for each barangay official
                    'email' => 'barangayofficial' . $barangay_id . '@example.com',
                    'password' => Hash::make('password123'),
                    'user_role' => 'barangay_official',
                    'contact_number' => '0912345678' . rand(1000, 9999),
                    'birthdate'=> '1997/06/01',
                    'position'=>'Kagawad',
                    'sex' => $barangay_id % 2 == 0 ? 'male' : 'female',
                    'municipality_id' => $municipalities[$i],
                    'barangay_id' => $barangay_id,
                ]);
            }
        }

        //public user
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'first_name' => 'Public',
                'last_name' => 'User ' . $i,
                'email' => 'publicuser' . $i . '@example.com',
                'password' => Hash::make('password123'),
                'user_role' => 'public_user',
                'contact_number' => '0912345678' . rand(1000, 9999),
                'birthdate'=> '2000/07/01',
                'sex' => $i % 2 == 0 ? 'female' : 'male', // Alternate between Male and Female
            ]);
        }

    }
}
