<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use App\Models\Species;
use App\Models\StrandedIncident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class StrandedIncidentController extends Controller
{
    //
    public function index()
    {
        $user = Auth::user();

        if (in_array($user->user_role, ['bpemo_admin', 'bpemo_staff'])) {
            $strandedIncidents = StrandedIncident::whereIn('report_status', ['pending', 'verified', 'completed'])
                ->with('reportActions')
                ->get();
        } elseif ($user->user_role === 'lgu_responder') {
            $strandedIncidents = StrandedIncident::whereIn('report_status', ['pending', 'verified', 'completed'])
                ->where('municipality_id', $user->municipality_id)
                ->with('reportActions')
                ->get();
        } elseif ($user->user_role === 'barangay_official') {
            $strandedIncidents = StrandedIncident::whereIn('report_status', ['pending', 'verified', 'completed'])
                ->where('barangay_id', $user->barangay_id)
                ->with('reportActions')
                ->get();
        } else {
            $strandedIncidents = StrandedIncident::whereIn('report_status', ['pending', 'verified', 'completed', 'resolved'])
                ->where('user_id', $user->id)
                ->with('reportActions')
                ->get();
        }

        return Inertia::render('manage-stranded-incident/index', [
            'strandedIncidents' => $strandedIncidents,
        ]);
    }

    public function indexResolvedIncidents()
    {
        $user = Auth::user();

        if (in_array($user->user_role, ['bpemo_admin', 'bpemo_staff'])) {
            $strandedIncidents = StrandedIncident::whereIn('report_status', ['false', 'resolved'])
                ->with('reportActions')
                ->get();
        } elseif ($user->user_role === 'lgu_responder') {
            $strandedIncidents = StrandedIncident::whereIn('report_status', ['false', 'resolved'])
                ->where('municipality_id', $user->municipality_id)
                ->with('reportActions')
                ->get();
        } elseif ($user->user_role === 'barangay_official') {
            $strandedIncidents = StrandedIncident::whereIn('report_status', ['false', 'resolved'])
                ->where('barangay_id', $user->barangay_id)
                ->with('reportActions')
                ->get();
        } else {
            abort(403);
        }

        return Inertia::render('manage-stranded-incident/resolved-incidents/index', [
            'strandedIncidents' => $strandedIncidents,
        ]);
    }


    public function createPage()
    {
        return Inertia::render('manage-stranded-incident/Create');
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        // Determine report status based on user role
        $reportStatus = ($user->user_role !== 'public_user') ? 'verified' : 'pending';

        // Validate the incoming request
        $request->validate([
            'certainty_level' => 'required|numeric',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i:s',
            'quantity' => 'required|numeric',
            'condition' => 'required|in:alive,dead',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'sea_state' => 'nullable|in:calm,moderate,rough',
            'weather' => 'nullable|in:sunny,cloudy,rainy',
            'beach_type' => 'nullable|in:mangrove,rocky,sandy,reef',
            'detailed_location' => 'nullable|string',
            'more_information' => 'nullable|string',
            'municipality_id' => 'required|exists:municipalities,id',
            'barangay_id' => 'required|exists:barangays,id',
            'mediaFiles' => 'nullable|array',
            'mediaFiles.*' => 'mimes:jpeg,png,jpg,gif,svg,mp4,mov,avi,wmv|max:10240',
        ]);

        // Create the strandedIncident
        $strandedIncident = StrandedIncident::create([
            'certainty_level' => $request->certainty_level,
            'date' => $request->date,
            'time' => $request->time,
            'species_involved' => $request->species_involved,
            'quantity' => $request->quantity,
            'condition' => $request->condition,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'sea_state' => $request->sea_state,
            'weather' => $request->weather,
            'beach_type' => $request->beach_type,
            'detailed_location' => $request->detailed_location,
            'more_information' => $request->more_information,
            'report_status' => $reportStatus,
            'municipality_id' => $request->municipality_id,
            'barangay_id' => $request->barangay_id,
            'is_active' => true,
            'is_false' => false,
            'user_id' => $user->id,
        ]);

        // Handle file uploads
        if ($request->hasFile('mediaFiles')) {
            foreach ($request->file('mediaFiles') as $mediaFile) {
                $path = $mediaFile->store('strandedIncident', 'public');

                MediaFile::create([
                    'path' => $path,
                    'name' => $mediaFile->getClientOriginalName(),
                    'file_for' => 'stranded_incident',
                    'type' => $mediaFile->getClientMimeType(),
                    'stranded_incident_id' => $strandedIncident->id,
                ]);
            }
        }

        return redirect()->route('stranded.incident.index')->with('success', 'Stranded Incident created successfully!');
    }

    public function view($id)
    {
        // Eager load necessary relationships
        $strandedIncident = StrandedIncident::with([
            'mediaFiles',
            'comments.user',
            'respondActions',
            'reportActions',
            'strandedSpecies'
            ])->findOrFail($id);

        // Map media files to include public URLs
        $strandedIncident->mediaFiles = $strandedIncident->mediaFiles->map(function ($file) {
            $file->url = asset('storage/' . $file->path);
            return $file;
        });

        $userId = Auth::id();
        $userRespondAction = $strandedIncident->respondActions->firstWhere('user_id', $userId);

        foreach ($strandedIncident->strandedSpecies as $strandedSpecies) {
            $species = Species::find($strandedSpecies->species_id);
            $strandedSpecies->species_name = $species ? $species->name : 'Unknown Species';
        }
        return Inertia::render('manage-stranded-incident/View', [
            'strandedIncident' => $strandedIncident,
            'respondActions' => $strandedIncident->respondActions->toArray(),
            'userRespondStatus' => $userRespondAction ? $userRespondAction->response_status : null,
            'strandedSpecies' => $strandedIncident->strandedSpecies->toArray(),
        ]);
    }


    //update page for public users
    public function updatePage($id)
    {
        $strandedIncident = StrandedIncident::findOrFail($id);
        if($strandedIncident->report_status!=='pending'){
            abort(403);
        }
        $strandedIncident->load('mediaFiles');
        $strandedIncident->mediaFiles = $strandedIncident->mediaFiles->map(function ($file) {
        $file->url = asset('storage/' . $file->path);
            return $file;
        });
        return Inertia::render('manage-stranded-incident/Update', ['strandedIncident' => $strandedIncident]);
    }

    // update for responders
    public function updateResponderPage($id)
    {
        $strandedIncident = StrandedIncident::with(
            'mediaFiles','respondActions','reportActions')
            ->findOrFail($id);

        $user = Auth::user();
        if ($user->user_role === 'barangay_official' && $strandedIncident->report_status === 'completed') {
            abort(403, 'Unauthorized action. Barangay officials cannot act on completed reports.');
        }
        $strandedIncident->mediaFiles = $strandedIncident->mediaFiles->map(function ($file) {
        $file->url = asset('storage/' . $file->path);
            return $file;
        });

        $userId = Auth::id();
        $userRespondAction = $strandedIncident->respondActions->firstWhere('user_id', $userId);

        return Inertia::render('manage-stranded-incident/UpdateResponder', [
            'strandedIncident' => $strandedIncident,
            'userRespondStatus' => $userRespondAction ? $userRespondAction->response_status : null,
        ]);
    }

    public function update(Request $request, $id)
    {

        // Validate incoming data
        $validated = $request->validate([
            'certainty_level' => 'required|numeric',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i:s',
            'quantity' => 'required|numeric',
            'condition' => 'required|in:alive,dead',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'sea_state' => 'nullable|in:calm,moderate,rough',
            'weather' => 'nullable|in:sunny,cloudy,rainy',
            'beach_type' => 'nullable|in:mangrove,rocky,sandy,reef',
            'detailed_location' => 'nullable|string',
            'more_information' => 'nullable|string',
            'municipality_id' => 'required|exists:municipalities,id',
            'barangay_id' => 'required|exists:barangays,id',
            'mediaFiles' => 'nullable|array',
            'mediaFiles.*' => 'mimes:jpeg,png,jpg,gif,svg,mp4,mov,avi,wmv|max:10240',
            'deletedImages' => 'nullable|array',
            'report_status' => 'nullable|in:pending,verified,completed,resolved,false'
        ]);

        $user = Auth::user();
        if ($user->user_role === 'public_user' && $request->report_status !== 'pending') {
            abort(403, 'Unauthorized action. The report is already reviewed by responders.');
        }
        // Find StrandedIncident to update
        $strandedIncident = StrandedIncident::findOrFail($id);
        $strandedIncident->update($validated);

        // Handle media file deletion
        if ($request->has('deletedImages')) {
            $deletedMediaIds = $request->input('deletedImages'); // Get the IDs of images to delete
            foreach ($deletedMediaIds as $deletedMediaId) {
                $media = MediaFile::find($deletedMediaId);
                if ($media) {
                    // Delete the file from storage
                    Storage::disk('public')->delete($media->path);
                    // Delete the media record from the database
                    $media->delete();
                }
            }
        }

        // Handle new media files upload
        if ($request->hasFile('mediaFiles')) {
            foreach ($request->file('mediaFiles') as $file) {
                if ($file->isValid()) {
                    $path = $file->store('strandedIncident', 'public');
                    $strandedIncident->mediaFiles()->create([
                        'path' => $path,
                        'name' => $file->getClientOriginalName(),
                        'file_for' => 'stranded_incident',
                        'type' => $file->getClientMimeType(),
                        'stranded_incident_id' => $strandedIncident->id,
                    ]);
                }
            }
        }

        // Redirect to the updated strandedIncident view with a success message
        return redirect()->route('stranded.incident.view', $id)
                        ->with('success', 'Stranded Incident updated successfully.');
    }

    // update report status
    public function complete($id)
    {
        $strandedIncident = StrandedIncident::findOrFail($id);

        if ($strandedIncident->report_status === 'verified') {
            $strandedIncident->update(['report_status' => 'completed']);

            return redirect()->back()->with('success', 'Incident marked as complete.');
        } else {
            abort(400, 'Invalid report status.');
        }
    }

    public function resolve($id)
    {
        $strandedIncident = StrandedIncident::findOrFail($id);

        if ($strandedIncident->report_status === 'completed') {
            $strandedIncident->update(['report_status' => 'resolved']);

            return redirect()->back()->with('success', 'Incident marked as resolved.');
        } else {
            abort(400, 'Invalid report status.');
        }
    }


    //archiving
    public function archive(Request $request, $id)
    {
        // Validate the request, ensuring the password is provided
        $request->validate([
            'password' => 'required|string',
        ]);

        // Check if the provided password matches the authenticated user's password
        $currentUser = Auth::user();
        if (!Hash::check($request->password, $currentUser->password)) {
            return back()->withErrors(['password' => 'The provided password is incorrect.']);
        }

        $strandedIncident = StrandedIncident::findOrFail($id);
        $strandedIncident->is_active = false;
        $strandedIncident->save();

        return redirect()->route('stranded.incident.view', ['id' => $id, 'message'=> 'Successfully archived stranded incident report'])->with('success', 'Stranded Incident archived successfully.');
    }

    public function unarchive(Request $request, $id)
    {
        // Validate the request, ensuring the password is provided
        $request->validate([
            'password' => 'required|string',
        ]);

        // Check if the provided password matches the authenticated user's password
        $currentUser = Auth::user();
        if (!Hash::check($request->password, $currentUser->password)) {
            return back()->withErrors(['password' => 'The provided password is incorrect.']);
        }

        $strandedIncident = StrandedIncident::findOrFail($id);
        $strandedIncident->is_active = true;
        $strandedIncident->save();

        return redirect()->route('stranded.incident.view', ['id' => $id, 'message'=> 'Successfully unarhived stranded incident'])->with('success', 'Stranded incident unarchived successfully.');
    }

}
