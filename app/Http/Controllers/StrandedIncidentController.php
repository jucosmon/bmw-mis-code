<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use App\Models\StrandedIncident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'certainty_level' => 'required|string|max:100',
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
        $strandedIncident = StrandedIncident::with('mediaFiles')->findOrFail($id);

            // Map media files to include public URLs
            $strandedIncident->mediaFiles = $strandedIncident->mediaFiles->map(function ($file) {
                $file->url = asset('storage/' . $file->path);
                return $file;
            });

        return Inertia::render('manage-stranded-incident/View', ['strandedIncident' => $strandedIncident]);
    }

    public function updatePage($id)
    {
        $strandedIncident = StrandedIncident::findOrFail($id);

        // Load media files and include public URLs
        $strandedIncident->load('mediaFiles');
        $strandedIncident->mediaFiles = $strandedIncident->mediaFiles->map(function ($file) {
        $file->url = asset('storage/' . $file->path);
            return $file;
        });
        return Inertia::render('manage-stranded-incident/Update', ['strandedIncident' => $strandedIncident]);
    }
        public function update(Request $request, $id)
        {
            // Validate incoming data
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'scientific_name' => 'nullable|string|max:100',
                'common_name' => 'nullable|string|max:100',
                'local_name' => 'nullable|string|max:100',
                'category' => 'required|in:marine_mammals,marine_turtles,sharks_rays',
                'description' => 'required|string',
                'conservation_status' => 'required|in:CR,NT,EN,DD,VU,NA,LC',
                'max_size' => 'nullable|numeric',
                'shape' => 'required|in:turtle-like,shark-like,dolphin-like,dugong-like,whale-like,ray-like',
                'is_dangerous' => 'nullable|boolean',
                'mediaFiles' => 'nullable|array',
                'mediaFiles.*' => 'nullable|image|mimes:jpeg,png,jpg,gif',
                'deletedImages' => 'nullable|array', // Ensure this matches the Vue component
            ]);

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
                            'file_for' => 'strandedIncident',
                            'type' => $file->getClientMimeType(),
                            'strandedIncident_id' => $strandedIncident->id,
                        ]);
                    }
                }
            }

            // Redirect to the updated strandedIncident view with a success message
            return redirect()->route('stranded.incident.view', $id)
                            ->with('success', 'Stranded Incident updated successfully.');
        }



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
