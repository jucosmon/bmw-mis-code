<?php

namespace App\Http\Controllers;

use App\Models\RespondAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            RespondAction::create([
                'response_status' => $request->status,
                'stranded_incident_id' => $strandedIncidentId,
                'user_id' => $userId,
            ]);
            $message = 'Respond action created successfully.';
        }

        return redirect()->back()->with('success', $message);
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'in:ongoing,unavailable,onsite',
        ]);

        $respondAction = RespondAction::findOrFail($id);

        $respondAction->update([
            'response_status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Respond action updated successfully.');
    }


    public function view(RespondAction $respondAction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    /**
     * Update the specified resource in storage.
     */
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RespondAction $respondAction)
    {
        //
    }
}
