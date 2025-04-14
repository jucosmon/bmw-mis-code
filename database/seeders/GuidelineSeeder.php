<?php

namespace Database\Seeders;

use App\Models\Guideline;
use App\Models\Item;
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
        $guidelines = [
            [
                'title' => 'What to do when encountering a marine turtle',
                'description' => 'A set of step-by-step procedures and best practices for LGU responders to properly handle, document, and respond to marine turtle incidents, including rescue, tagging, rehabilitation, and release.',
                'user_role' => 'lgu_responder',
                'category' => 'marine_turtles',
                'items' => [
                    'Report all marine turtle incidents immediately to the nearest DENR office or designated authority.',
                    'Approach turtles calmly and quietly, especially nesters — avoid loud sounds and bright lights.',
                    'Restrain the turtle only when necessary using a damp cloth to cover the eyes (not nostrils).',
                    'Do not lift by the flippers — use a stretcher or carry by supporting the carapace.',
                    'Keep the turtle wet and shaded during handling and transport using soaked cloths or towels.',
                    'Transport turtles plastron-down, never upside down. Avoid vibrations and overheating.',
                    'If tagging is needed, ensure it\'s done by authorized personnel only using official tags.',
                    'Use the Tagging Data Sheet (MT01) and record CCL/CCW, tag number, and location accurately.',
                    'Release turtles within 2 hours after nesting or tagging; avoid unnecessary delays.',
                    'For stranding cases, assess body condition using the manual’s stranding codes (Codes 1–6).',
                    'Use correct procedures for live vs. dead turtle response (Code 1 vs. Codes 2–6).',
                    'For dead turtles, follow proper disposal procedures and submit necropsy forms if needed.',
                    'Collect data on-site using forms MT01–MT06. Include photos, measurements, and coordinates.',
                    'Engage local communities for awareness and reporting; educate without endangering the turtle.',
                ]
            ],
            [
                'title' => 'Response Guidelines for Stranded Marine Turtles',
                'description' => 'This guideline provides barangay officials with step-by-step instructions on how to respond to reports of stranded marine turtles, whether alive or dead. It covers identification, proper handling, rescue coordination, documentation, and disposal to ensure the protection of both the animal and public health while supporting conservation goals.',
                'user_role' => 'barangay_official',
                'category' => 'marine_turtles',
                'items' => [
                    'Record initial report details: informant’s contact info, turtle location, and visible condition.',
                    'Identify the stranding code (alive, dead-fresh, decomposed, skeletal, destroyed) to determine response​.',
                    'Mobilize a response team ideally composed of a coordinator, data collector, and documenter​.',
                    'Assess the turtle\'s condition by checking movement, blinking, and breathing​.',
                    'If alive and healthy, release the turtle immediately after documentation and tagging​.',
                    'If weak or injured, transport to a DENR-recognized rehabilitation center.',
                    'For dead turtles, collect data and take photos (top view, tag, injuries)​.',
                    'Submit MT02 stranding form and photos to the DENR for record keeping​.',
                    'Bury the carcass at least one meter deep, away from communities and scavengers​.',
                    'Educate bystanders on marine turtle conservation during the response activity​.',
                    'Never tie, flip, hold captive, or keep the turtle out of water for too long​.',
                    'Always wear gloves and avoid contact with turtle fluids during handling.',
                ]
            ],
            [
                'title' => 'Public Guidelines for Reporting and Responding to Stranded Marine Turtles',
                'description' => 'This guideline provides the general public with easy-to-follow instructions on what to do when they encounter a stranded marine turtle, ensuring both their safety and the welfare of the turtle. The steps focus on proper observation, reporting to authorities, and avoiding harmful actions.',
                'user_role' => 'public_user',
                'category' => 'marine_turtles',
                'items' => [
                    'Do not touch or move the turtle immediately. Observe it from a safe distance​.',
                    'Take note of key details: location, time spotted, condition (alive or dead), and any visible injuries.',
                    'Report the sighting to the nearest DENR office or barangay officials as soon as possible​.',
                    'If taking photos, avoid using flash and do not disturb the turtle while doing so.',
                    'Never attempt to return the turtle to the sea, especially if it looks weak or injured. Wait for trained responders​.',
                    'Do not tie, flip, drag, or sit on the turtle. Avoid any form of physical interaction​.',
                    'Keep pets, children, and crowds away from the turtle to avoid stressing it out.',
                    'If possible, create shade (like using an umbrella) if the turtle is under direct sunlight.',
                    'Do not collect eggs, shells, or any part of the turtle. These are protected by law.',
                    'Spread awareness by encouraging others to report sightings to authorities instead of intervening.',
                    'If the turtle is dead, still report it. It helps experts study causes of death and improve conservation​.',
                    'Wait for guidance. Once the proper team arrives, follow their instructions or assist if requested.',
                ]
            ],
            // Shark Rays
            [
                'title' => 'Public Guidelines for Reporting Stranded Sharks and Rays',
                'description' => 'Provides step-by-step actions for the public to safely and responsibly report stranded sharks and rays. The focus is on safety, reporting, and avoiding harm to the animal.',
                'user_role' => 'public_user',
                'category' => 'sharks_rays',
                'items' => [
                    'Do not approach or touch the animal.',
                    'Record the location, time, and visible condition of the animal (alive or dead).',
                    'Call your barangay officials or DA-BFAR office immediately.',
                    'Keep the area clear of crowds, especially children and pets.',
                    'If taking photos, avoid using flash and do not get too close.',
                    'Do not attempt to drag or return the animal to sea.',
                    'Educate others nearby not to disturb the animal.',
                    'Stay on site if possible until authorities arrive, but maintain a safe distance.'
                ]
            ],

            [
                'title' => 'Barangay-Level Guidelines for Stranded Shark and Ray Response',
                'description' => 'Steps for barangay officials to initiate local response, crowd control, and coordination with technical agencies like BPEMO and BFAR.',
                'user_role' => 'barangay_official',
                'category' => 'sharks_rays',
                'items' => [
                    'Validate the public report and document initial info (time, location, animal condition).',
                    'Secure the site and prevent the public from touching the animal.',
                    'Immediately notify LGU responder or BPEMO.',
                    'Assign a barangay tanod or staff to assist with crowd control.',
                    'Assist response team in locating the animal quickly and safely.',
                    'Support documentation by sharing witness accounts or phone photos.',
                    'Join awareness efforts during the incident (talk to curious locals).',
                    'Support post-response data collection and carcass disposal if needed'
                ]
            ],

            [
                'title' => 'Municipal/City LGU Protocols for Shark and Ray Stranding Response',
                'description' => 'Guidelines for designated LGU responders to conduct technical field assessment, support data collection, and work with BPEMO and BFAR.',
                'user_role' => 'lgu_responder',
                'category' => 'sharks_rays',
                'items' => [
                    'Bring response kit: gloves, forms, camera, tape, alcohol.',
                    'Identify stranding code (1–6) to determine if alive or decomposed.',
                    'Take photos from dorsal and ventral views.',
                    'Assist in assessing vital signs (movement, breathing).',
                    'For live animals, coordinate immediate release or rehab transport.',
                    'For dead animals, collect basic measurements and assist in burial.',
                    'Submit reports to BPEMO and BFAR.',
                    'Conduct short community orientation on what happened and what was done.'
                ]
            ],

             // Marine Mammals
             [
                'title' => 'Public Guidelines for Reporting Marine Mammal Strandings',
                'description' => 'Basic steps the public can take to ensure safety and accurate reporting during marine mammal strandings, helping conservation teams respond effectively.',
                'user_role' => 'public_user',
                'category' => 'marine_mammals',
                'items' => [
                    'Do not touch, push, or attempt to return the animal to the sea.',
                    'Keep a safe distance from the tail and mouth.',
                    'Avoid making noise or crowding the animal.',
                    'Take note of the animal’s location, time of sighting, and visible condition.',
                    'Immediately report to barangay officials or nearest DA-BFAR/DENR.',
                    'Do not take body parts or items from dead marine mammals.',
                    'Stay on site to help guide responders if possible.',
                    'Share information respectfully to raise awareness, not fear.'
                ]
            ],

            [
                'title' => 'Barangay Guidelines for Responding to Marine Mammal Strandings',
                'description' => 'Outlines duties of barangay officials during strandings including coordination, initial crowd control, and communication with authorities.',
                'user_role' => 'barangay_official',
                'category' => 'marine_mammals',
                'items' => [
                    'Confirm report and respond to the site immediately.',
                    'Clear the area and prevent the public from disturbing the animal.',
                    'Inform DA-BFAR or DENR offices immediately.',
                    'Provide basic support like shade or water if the animal is alive.',
                    'Assist the response team with site access and manpower.',
                    'Support data gathering (e.g., witness accounts, local knowledge).',
                    'Monitor the site until professional responders arrive.',
                    'Help with disposal procedures if the animal is dead.'
                ]
            ],

            [
                'title' => 'LGU Protocols for Marine Mammal Stranding Response',
                'description' => 'Guides LGU technical staff in conducting stranding assessments, documentation, and coordination with BPEMO, BFAR, and DENR',
                'user_role' => 'lgu_responder',
                'category' => 'marine_mammals',
                'items' => [
                    'Arrive on-site with a standard response kit and documentation tools.',
                    'Confirm the stranding code (1 to 6) and assess the animal’s condition.',
                    'Assist in vital signs check and apply first response procedures.',
                    'Help tag or take basic measurements if trained.',
                    'Support transport of live animals or burial of carcasses.',
                    'Ensure proper photo documentation and submit reports.',
                    'Educate the public during the response event.',
                    'Coordinate directly with BPEMO or national offices for next steps.'
                ]
            ],



        ];

        foreach ($guidelines as $data) {
            $guideline = Guideline::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'user_role' => $data['user_role'],
                'category' => $data['category'],
                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            foreach ($data['items'] as $item) {
                $guideline->items()->create([
                    'text' => $item,
                    'count' => count($data['items']),
                    'guideline_id' => $guideline->id,
                ]);
            }
        }
    }
}
