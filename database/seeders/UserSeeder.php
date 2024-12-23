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

        User::create([
            'first_name' => 'Admin',
            'last_name' => '0',
            'email' => 'bpemoadmin@example.com',
            'password' => Hash::make('password123'),
            'user_role' => 'bpemo_admin',
            'contact_number' => '0912345653',
            'birthdate'=> '2003/12/01',
            'position'=>'administrator'
        ]);

        User::create([
            'first_name' => 'Staff',
            'last_name' => 'User',
            'email' => 'bpmeostaff@example.com',
            'password' => Hash::make('password123'),
            'user_role' => 'bpemo_staff',
            'contact_number' => '0912345653',
            'birthdate'=> '2003/12/01',
            'position'=>'Marine staff'
        ]);

        User::create([
            'first_name' => 'LGU',
            'last_name' => 'Responder',
            'email' => 'lguresponder@example.com',
            'password' => Hash::make('password123'),
            'user_role' => 'lgu_responder',
            'contact_number' => '0912345653',
            'birthdate'=> '2003/12/01',
            'position'=>'LGU staff'
        ]);

        User::create([
            'first_name' => 'Barangay',
            'last_name' => 'Official',
            'email' => 'barangayofficial@example.com',
            'password' => Hash::make('password123'),
            'user_role' => 'barangay_official',
            'contact_number' => '0912345653',
            'birthdate'=> '2003/12/01',
            'position'=>'Kagawad'
        ]);

        User::create([
            'first_name' => 'Public',
            'last_name' => 'User',
            'email' => 'publicuser@example.com',
            'password' => Hash::make('password123'),
            'user_role' => 'public_user',
            'contact_number' => '0912345653',
            'birthdate'=> '2003/12/01',
        ]);


    }
}
