<?php

namespace App\Http\Controllers;

use App\Models\StrandedIncident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StrandedIncidentController extends Controller
{
    //
    public function index()
    {
        $user = Auth::user();

        if (in_array($user->user_role, ['bpemo_admin', 'bpemo_staff'])) {
            $strandedIncidents = StrandedIncident::whereIn('report_status', ['pending', 'verified', 'completed'])
                ->with('userActions')
                ->get();
        } elseif ($user->user_role === 'lgu_responder') {
            $strandedIncidents = StrandedIncident::whereIn('report_status', ['pending', 'verified', 'completed'])
                ->where('municipality_id', $user->municipality_id)
                ->with('userActions')
                ->get();
        } elseif ($user->user_role === 'barangay_official') {
            $strandedIncidents = StrandedIncident::whereIn('report_status', ['pending', 'verified', 'completed'])
                ->where('barangay_id', $user->barangay_id)
                ->with('userActions')
                ->get();
        } else {
            $strandedIncidents = StrandedIncident::whereIn('report_status', ['pending', 'verified', 'completed', 'resolved'])
                ->where('user_id', $user->id)
                ->with('userActions')
                ->get();
        }

        return Inertia::render('manage-stranded-incident/index', [
            'strandedIncidents' => $strandedIncidents,
        ]);
    }

}
