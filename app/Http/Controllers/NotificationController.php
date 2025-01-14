<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Sighting;
use App\Models\StrandedIncident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{

    // Fetch notifications for a user
    public function index(Request $request)
    {
        $user = Auth::user();
        $notifications = collect(); // Initialize an empty collection for notifications

        if ($user->user_role === 'public_user') {
            $strandedIncidents = StrandedIncident::where('user_id', $user->id)->pluck('id');
            $sightings = Sighting::where('user_id', $user->id)->pluck('id');

            $notifications = Notification::whereIn('stranded_incident_id', $strandedIncidents)
                ->orWhereIn('sighting_id', $sightings)
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($user->user_role === 'barangay_official') {
            $strandedIncidents = StrandedIncident::where('barangay_id', $user->barangay_id)->pluck('id');
            $sightings = Sighting::where('user_id', $user->id)->pluck('id');

            $notifications = Notification::whereIn('stranded_incident_id', $strandedIncidents)
                ->orWhereIn('sighting_id', $sightings)
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($user->user_role === 'lgu_responder') {
            $strandedIncidents = StrandedIncident::where('municipality_id', $user->municipality_id)->pluck('id');
            $sightings = Sighting::where('user_id', $user->id)->pluck('id');

            $notifications = Notification::whereIn('stranded_incident_id', $strandedIncidents)
                ->orWhereIn('sighting_id', $sightings)
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($user->user_role === 'bpemo_staff' || $user->user_role === 'bpemo_admin') {
            $strandedIncidents = StrandedIncident::pluck('id');
            $sightings = Sighting::pluck('id');

            $notifications = Notification::whereIn('stranded_incident_id', $strandedIncidents)
                ->orWhereIn('sighting_id', $sightings)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            abort(403, 'Invalid');
        }

        return response()->json($notifications);
    }

    // Mark a notification as read
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json($notification);
    }
}
