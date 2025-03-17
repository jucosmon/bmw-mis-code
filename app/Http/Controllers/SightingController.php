<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\MediaFile;
use App\Models\Municipality;
use App\Models\Notification;
use App\Models\SightedSpecies;
use App\Models\Sighting;
use App\Models\Species;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SightingController extends Controller
{
    //
    public function index()
    {
        $user = Auth::user();

        // Retrieve pending sightings based on user role with sightedSpecies relationship
        $sightings = Sighting::with('sightedSpecies.species')->where('is_active', true);

        if ($user->user_role === 'public_user') {
            $sightings->where('user_id', $user->id);
        }else if($user->user_role === 'lgu_responder'){
            $sightings->where('municipality_id', $user->municipality_id);
        }else if($user->user_role === 'barangay_official'){
            $sightings->where('barangay_id', $user->barangay_id);
        }

        return Inertia::render('manage-sighting/index', [
            'sightings' => $sightings->get(),
            'success' => session('success'),
            'municipalities' => Municipality::all(),
            'barangays' => Barangay::all()
        ]);
    }

    public function createPage()
    {
        $species = Species::get();
        return Inertia::render('manage-sighting/Create', [
            'species' => $species,
            'municipalities' => Municipality::all(),
            'barangays' => Barangay::all()
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();

        // Determine report status based on user role
        $reportStatus = ($user->user_role === 'bpemo_admin' ||$user->user_role === 'bpemo_staff') ? 'verified' : 'pending';

        // Validate the incoming request
        $request->validate([
            'certainty_level' => 'required|numeric',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i:s',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'detailed_location' => 'required|string',
            'more_information' => 'nullable|string',
            'municipality_id' => 'nullable|exists:municipalities,id',
            'barangay_id' => 'nullable|exists:barangays,id',
            'mediaFiles' => 'nullable|array',
            'mediaFiles.*' => 'mimes:jpeg,png,jpg,gif,svg,mp4,mov,avi,wmv|max:10240',
            'sightedSpecies' => 'required|array|min:1',
            'sightedSpecies.*.species_id' => 'nullable|exists:species,id',
            'sightedSpecies.*.size' => 'required|in:tiny,small,medium,large,very_large,giant',
            'sightedSpecies.*.species_description' => 'nullable|string',
            'sightedSpecies.*.behavior_observed' => 'required|string'
        ]);

        // Create the sighting
        $sighting = Sighting::create([
            'certainty_level' => $request->certainty_level,
            'date' => $request->date,
            'time' => $request->time,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'detailed_location' => $request->detailed_location,
            'more_information' => $request->more_information,
            'report_status' => $reportStatus,
            'municipality_id' => $request->municipality_id,
            'barangay_id' => $request->barangay_id,
            'is_active' => true,
            'user_id' => $user->id,
        ]);


        // Handle file uploads
        if ($request->hasFile('mediaFiles')) {
            foreach ($request->file('mediaFiles') as $mediaFile) {
                $path = $mediaFile->store('sighting', 'public');

                MediaFile::create([
                    'path' => $path,
                    'name' => $mediaFile->getClientOriginalName(),
                    'file_for' => 'sighting',
                    'type' => $mediaFile->getClientMimeType(),
                    'sighting_id' => $sighting->id,
                ]);
            }
        }

        foreach ($request->sightedSpecies as $sightedSpecies) {
            SightedSpecies::create([
                'size' => $sightedSpecies['size'], // Use array notation
                'species_description' => $sightedSpecies['species_description'], // Use array notation
                'behavior_observed' => $sightedSpecies['behavior_observed'], // Use array notation
                'species_id' => $sightedSpecies['species_id'], // Use array notation
                'sighting_id' => $sighting->id,
            ]);
        }

        $this->createNotification($sighting, 'create');

        return redirect()->route('sighting.index')
        ->with('success', 'You have successfully created a sighting report');
    }

    protected function createNotification( $sighting, $action)
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
                $userRole = 'Public User';
        }

        if($action ==='create'){
            // Create the notification
            Notification::create([
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} reported a new sighting.",
                'category' => 'general',
                'notif_for' => 'all',
                'type' => 'sighting',
                'is_read' => false,
                'created_at' => now(),
                'sighting_id' => $sighting->id,
                'user_id' => null,
                'comment_id' => null,
            ]);

        } else if($action === 'verified'){
            Notification::create([
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} already reviewed the report and verified the sighting.",
                'category' => 'general',
                'notif_for' => 'all',
                'type' => 'sighting',
                'is_read' => false,
                'created_at' => now(),
                'sighting_id' => $sighting->id,
                'user_id' => null,
                'comment_id' => null,
            ]);
        } else if($action === 'archived'){
            Notification::create([
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} archived the reported sighting.",
                'category' => 'false',
                'notif_for' => 'all',
                'type' => 'sighting',
                'is_read' => false,
                'created_at' => now(),
                'sighting_id' => $sighting->id,
                'user_id' => null,
                'comment_id' => null,
            ]);
        } else if($action === 'unarchived'){
            Notification::create([
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} unarchived an archived sighting.",
                'category' => 'general',
                'notif_for' => 'all',
                'type' => 'sighting',
                'is_read' => false,
                'created_at' => now(),
                'sighting_id' => $sighting->id,
                'user_id' => null,
                'comment_id' => null,
            ]);
        } else if($action === 'false'){
            Notification::create([
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} is already onsite and marked the reported sighting as false.",
                'category' => 'false',
                'notif_for' => 'all',
                'type' => 'sighting',
                'is_read' => false,
                'created_at' => now(),
                'sighting_id' => $sighting->id,
                'user_id' => null,
                'comment_id' => null,
            ]);
        } else if($action === 'unverify'){
            Notification::create([
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} is unverified a verified sighting.",
                'category' => 'general',
                'notif_for' => 'all',
                'type' => 'sighting',
                'is_read' => false,
                'created_at' => now(),
                'sighting_id' => $sighting->id,
                'user_id' => null,
                'comment_id' => null,
            ]);
        }
        else {
            abort(403, 'Invalid action');
        }

    }

    public function view($id)
    {
        // Eager load necessary relationships
        $sighting = Sighting::with([
            'mediaFiles',
            'sightedSpecies',
            'user'
            ])->findOrFail($id);

        // Map media files to include public URLs
        $sighting->mediaFiles = $sighting->mediaFiles->map(function ($file) {
            $file->url = asset('storage/' . $file->path);
            return $file;
        });

        foreach ($sighting->sightedSpecies as $sightedSpecies) {
            $species = Species::find($sightedSpecies->species_id);
            $sightedSpecies->species_name = $species ? $species->name : 'Unknown Species';
        }
        return Inertia::render('manage-sighting/View', [
            'sighting' => $sighting,
            'sightedSpecies' => $sighting->sightedSpecies->toArray(),
            'success' => session('success'),
            'municipalities' => Municipality::all(),
            'barangays' => Barangay::all()
        ]);
    }

    //update page for public users
    public function updatePage($id)
    {
        $user = Auth::user();
        $sighting = Sighting::with(['sightedSpecies'])->findOrFail($id);

        // Load media files
        $sighting->mediaFiles = $sighting->mediaFiles->map(function ($file) {
            $file->url = asset('storage/' . $file->path);
            return $file;
        });


        $species = Species::get();
        return Inertia::render('manage-sighting/Update', [
            'sighting' => $sighting,
            'species' => $species,
            'municipalities' => Municipality::all(),
            'barangays' => Barangay::all()
        ]);
    }

    // update for responders
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'certainty_level' => 'required|numeric',
            'date' => 'required|date',
            'time' => 'required|date_format:H:i:s',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'detailed_location' => 'required|string',
            'more_information' => 'nullable|string',
            'municipality_id' => 'nullable|exists:municipalities,id',
            'barangay_id' => 'nullable|exists:barangays,id',
            'mediaFiles' => 'nullable|array',
            'mediaFiles.*' => 'mimes:jpeg,png,jpg,gif,svg,mp4,mov,avi,wmv|max:10240',
            'deletedImages' => 'nullable|array',
            'report_status' => 'nullable|in:pending,verified,false',
            'sightedSpecies' => 'required|array|min:1',
            'sightedSpecies.*.id' => 'nullable|exists:sighted_species,id',
            'sightedSpecies.*.species_id' => 'nullable|exists:species,id',
            'sightedSpecies.*.size' => 'required|in:tiny,small,medium,large,very_large,giant',
            'sightedSpecies.*.species_description' => 'nullable|string',
            'sightedSpecies.*.behavior_observed' => 'required|string',
            'deletedSightedSpecies' => 'nullable|array',
        ]);

        $user = Auth::user();
        if (($user->user_role !== 'bpemo_admin' && $user->user_role !== 'bpemo_staff') && ($request->report_status !== 'pending' || $request->is_active === 'false')) {
            abort(403, 'Unauthorized action. The report is already verified as true.');
        }

        $sighting = Sighting::findOrFail($id);

        // Store the old report status to check for changes
        $oldReportStatus = $sighting->report_status;

        $sighting->update($validated);

        // Check if the report status has changed
        if ($oldReportStatus !== $validated['report_status']) {
            if ($validated['report_status'] === 'false') {
                Notification::where('sighting_id', $id)->delete();
            }else if($validated['report_status'] === 'verified' && $oldReportStatus === 'false'){
                $successMessage = 'You have successfully verified a falsed sighting report!';
                $sighting->is_active = true;
                $sighting->save();
            } else if($validated['report_status'] === 'verified'){
                $successMessage = 'You have successfully verified a sighting report!';
            }
            $this->createNotification($sighting, $validated['report_status']);
        } else {
            $successMessage = 'You have successfully updated a sighting report!';
        }

        // Handle deleted images
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
                    $path = $file->store('sighting', 'public');
                    $sighting->mediaFiles()->create([
                        'path' => $path,
                        'name' => $file->getClientOriginalName(),
                        'file_for' => 'sighting',
                        'type' => $file->getClientMimeType(),
                        'sighting_id' => $sighting->id,
                    ]);
                }
            }
        }

       // Handle deleted sighted species
        if ($request->has('deletedSightedSpecies')) {
            $deletedSightedSpeciesIds = $request->input('deletedSightedSpecies');

            foreach ($deletedSightedSpeciesIds as $deletedSightedSpeciesId) {
                $sightedSpecies = SightedSpecies::find($deletedSightedSpeciesId);
                if ($sightedSpecies) {
                    $sightedSpecies->delete(); // This should work for soft deletes
                    // or use $sightedSpecies->forceDelete(); for permanent deletion
                } else {
                    dd('Sighted Species not found for ID:', $deletedSightedSpeciesId);
                }
            }
        }

        // Handle updated sighted species
        foreach ($validated['sightedSpecies'] as $sightedSpecies) {
            if (isset($sightedSpecies['id']) && !is_null($sightedSpecies['id'])) {
                // Update existing species
                $sightedSpeciesModel = SightedSpecies::find($sightedSpecies['id']);
                if ($sightedSpeciesModel) {
                    $sightedSpeciesModel->update($sightedSpecies);
                }
            } else {

                // Create new species
                SightedSpecies::create([
                    'size' => $sightedSpecies['size'],
                    'species_description' => $sightedSpecies['species_description'],
                    'behavior_observed' => $sightedSpecies['behavior_observed'],
                    'species_id' => $sightedSpecies['species_id'],
                    'sighting_id' => $sighting->id,
                ]);
            }
        }

        if($validated['report_status'] === 'false'){
            return redirect()->route('sighting.index')
                        ->with('success', 'You have successfully marked a sighting report as false.');
        }

        // Redirect to the updated sighting view with a success message
        return redirect()->route('sighting.view', $id)
                        ->with('success', $successMessage);
    }

    public function unverify(Request $request, $id)
    {
        // Validate the request, ensuring the password is provided
        $request->validate([
            'unverify_password' => 'required|string',
        ]);

        $currentUser = Auth::user();
        if (!Hash::check($request->unverify_password, $currentUser->password)) {
            return back()->withErrors(['unverify_password' => 'The provided password is incorrect.']);
        }

        $sighting = Sighting::findOrFail($id);
        $sighting->report_status = 'pending';
        $sighting->save();


        $this->createNotification($sighting, 'unverify');

        return redirect()->route('sighting.view', ['id' => $id])
            ->with('success', 'You have successfully unverified a sighting report.');
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

        $sighting = Sighting::findOrFail($id);
        $sighting->is_active = false;
        $sighting->save();

        // Delete previous notifications related to this sighting
        Notification::where('sighting_id', $id)->delete();

        $this->createNotification($sighting, 'archived');

        return redirect()->route('sighting.view', ['id' => $id])
            ->with('success', 'You have successfully archived a sighting report.');
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

        $sighting = Sighting::findOrFail($id);
        $sighting->is_active = true;
        $sighting->save();

        $this->createNotification($sighting, 'unarchived');

        return redirect()->route('sighting.view', ['id' => $id])
            ->with('success', 'You have successfully unarchived a sighting report.');
    }

}
