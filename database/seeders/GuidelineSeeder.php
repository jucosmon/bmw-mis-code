<?php

namespace Database\Seeders;

use App\Models\Guideline;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GuidelineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample data for guidelines
        $userRoles = ['lgu_responder', 'barangay_official', 'public_user'];
        $categories = ['marine_turtles', 'marine_mammals', 'sharks_rays'];

        foreach ($userRoles as $userRole) {
            foreach ($categories as $category) {
                for ($i = 0; $i < 2; $i++) { // Create 2 records for each category
                    $guideline = Guideline::create([
                        'title' => "Sample Guideline Title for $userRole - $category " . ($i + 1),
                        'description' => "This is a sample description for $userRole in the category of $category. Additional details can be added here.",
                        'user_role' => $userRole,
                        'category' => $category,
                        'is_active' => true,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);

                    // Create items for each guideline
                    for ($j = 0; $j < 3; $j++) { // Create 3 items for each guideline
                        $guideline->items()->create([
                            'count' => $j + 1, // Example count
                            'text' => "Sample item text for $userRole - $category " . ($i + 1) . " - Item " . ($j + 1),
                            'guideline_id' => $guideline->id,
                        ]);
                    }
                }
            }
        }
    }
}
