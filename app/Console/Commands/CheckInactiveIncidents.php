<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\StrandedIncident;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckInactiveIncidents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-inactive-incidents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for inactive incidents and notify responders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get incidents that are still active and haven't been updated in 30 minutes
        $inactiveIncidents = StrandedIncident::where('is_active', true)
            ->where('report_status', 'pending')
            ->where(function ($query) {
                $query->whereDoesntHave('respondActions')
                      ->orWhereHas('respondActions', function ($query) {
                          $query->where('status', '!=', 'unavailable');
                      }, '<', 1);
            })
            ->where('updated_at', '<', Carbon::now()->subMinutes(30))
            ->get();

        if ($inactiveIncidents->isEmpty()) {
            $this->info('No inactive incidents found.');
            return;
        }

        // Get all responders (you might need to adjust this based on your user roles)
        $responders = User::whereIn('user_role', ['bpemo_admin', 'bpemo_staff', 'lgu_responder', 'barangay_official'])->get();

        foreach ($inactiveIncidents as $incident) {
            $this->info("Processing inactive incident #{$incident->id}");

            foreach ($responders as $responder) {
                Notification::create([
                    //'link' => "/incidents/{$incident->id}",
                    'content' => "Incident #{$incident->id} has been inactive for 30+ minutes. Please review.",
                    'category' => 'general',
                    'notif_for' => 'responders',
                    'type' => 'stranding',
                    'is_read' => false,
                    'created_at' => now(),
                    'stranded_incident_id' => $incident->id,
                    'user_id' => null,
                    'comment_id' => null,
                ]);
            }
        }

        $this->info("Created notifications for {$inactiveIncidents->count()} inactive incidents.");
    }
}
