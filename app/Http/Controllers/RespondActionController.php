<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\RespondAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RespondActionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function respond(Request $request)
    {
        $request->validate([
            'status' => 'in:ongoing,unavailable,onsite',
        ]);

        $userId = Auth::id();
        $strandedIncidentId = $request->id;

        // Check if a RespondAction record already exists for this user and incident
        $respondAction = RespondAction::where('stranded_incident_id', $strandedIncidentId)
                                        ->where('user_id', $userId)
                                        ->first();

        // If a record exists, update it; otherwise, create a new record
        if ($respondAction) {
            // Update the existing record
            $respondAction->update([
                'response_status' => $request->status,
            ]);
            $message = 'Respond action updated successfully.';
        } else {
            // Create a new record
            $respondAction = RespondAction::create([
                'response_status' => $request->status,
                'stranded_incident_id' => $strandedIncidentId,
                'user_id' => $userId,
            ]);
            $message = 'Respond action created successfully.';
        }

        $this->createNotification($respondAction);

        return redirect()->route('stranded.incident.view', ['id' => $strandedIncidentId])
                ->with('success', $message);
    }

    protected function createNotification( $respondAction)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Determine the user role
        $userRole = '';
        switch ($user->user_role) {
            case "bpemo_admin":
                $userRole = "BPEMO Administrator";
                break;
            case "bpemo_staff":
                $userRole = "BPEMO Staff";
                break;
            case "lgu_responder":
                $userRole = "LGU Responder";
                break;
            case "barangay_official":
                $userRole = "Barangay Official";
                break;
            default:
                $userRole = 'Unknown Responder';
        }

        if($respondAction->response_status ==='ongoing'){
            // Create the notification
            Notification::create([
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} is now ongoing to the stranded incident.",
                'category' => 'general',
                'notif_for' => 'responders',
                'type' => 'stranding',
                'is_read' => false,
                'created_at' => now(),
                'stranded_incident_id' => $respondAction->stranded_incident_id,
                'user_id' => null,
                'comment_id' => null,
            ]);

        } else if($respondAction->response_status === 'unavailable'){
            Notification::create([
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} is currently unavailable to respond in the stranded incident.",
                'category' => 'general',
                'notif_for' => 'responders',
                'type' => 'stranding',
                'is_read' => false,
                'created_at' => now(),
                'stranded_incident_id' => $respondAction->stranded_incident_id,
                'user_id' => null,
                'comment_id' => null,
            ]);
        } else if($respondAction->response_status === 'onsite'){
            Notification::create([
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} is already on site and on review of the stranded incident.",
                'category' => 'general',
                'notif_for' => 'responders',
                'type' => 'stranding',
                'is_read' => false,
                'created_at' => now(),
                'stranded_incident_id' => $respondAction->stranded_incident_id,
                'user_id' => null,
                'comment_id' => null,
            ]);
        }
        else {
            abort(403, 'Invalid action');
        }

    }
}
