<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\MediaFile;
use App\Models\Municipality;
use App\Models\Notification;
use App\Models\RespondAction;
use App\Models\Species;
use App\Models\StrandedIncident;
use App\Traits\HandlesFalseReports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class StrandedIncidentController extends Controller
{
    use HandlesFalseReports;
    //
    public function index()
    {
        $user = Auth::user();

        if (in_array($user->user_role, ['bpemo_admin', 'bpemo_staff'])) {
            $strandedIncidents = StrandedIncident::whereIn('report_status', ['pending', 'verified', 'completed'])
                ->where('is_active', true)
                ->get();
        } elseif ($user->user_role === 'lgu_responder') {
            $strandedIncidents = StrandedIncident::whereIn('report_status', ['pending', 'verified', 'completed'])
                ->where('municipality_id', $user->municipality_id)
                ->where('is_active', true)
                ->get();
        } elseif ($user->user_role === 'barangay_official') {
            $strandedIncidents = StrandedIncident::whereIn('report_status', ['pending', 'verified', 'completed'])
                ->where('barangay_id', $user->barangay_id)
                ->where('is_active', true)
                ->get();
        } else {
            $strandedIncidents = StrandedIncident::whereIn('report_status', ['pending', 'verified', 'completed', 'resolved'])
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->get();
        }

        return Inertia::render('manage-stranded-incident/index', [
            'strandedIncidents' => $strandedIncidents,
            'success' => session('success'),
            'municipalities' => Municipality::all(),
            'barangays' => Barangay::all()
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
            'success' => session('success'),
            'municipalities' => Municipality::all(),
            'barangays' => Barangay::all()
        ]);
    }


    public function createPage()
    {
        return Inertia::render('manage-stranded-incident/Create', [
            'municipalities' => Municipality::all(),
            'barangays' => Barangay::all()
        ]);
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
            'detailed_location' => 'required|string',
            'more_information' => 'nullable|string',
            'municipality_id' => 'nullable|exists:municipalities,id',
            'barangay_id' => 'nullable|exists:barangays,id',
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

        $this->createNotification($strandedIncident, 'create');

        return redirect()->route('stranded.incident.index')
        ->with('success', 'You have successfully created a stranded incident report');
    }

    protected function createNotification( $strandedIncident, $action)
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
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} reported a new stranding incident.",
                'category' => 'general',
                'notif_for' => 'responders',
                'type' => 'stranding',
                'is_read' => false,
                'created_at' => now(),
                'stranded_incident_id' => $strandedIncident->id,
                'user_id' => null,
                'comment_id' => null,
            ]);

        } else if($action === 'verified'){
            Notification::create([
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} is already onsite and verified the stranding incident.",
                'category' => 'general',
                'notif_for' => 'all',
                'type' => 'stranding',
                'is_read' => false,
                'created_at' => now(),
                'stranded_incident_id' => $strandedIncident->id,
                'user_id' => null,
                'comment_id' => null,
            ]);
        } else if($action === 'completed'){
            Notification::create([
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} marked the stranding incident as completed.",
                'category' => 'general',
                'notif_for' => 'all',
                'type' => 'stranding',
                'is_read' => false,
                'created_at' => now(),
                'stranded_incident_id' => $strandedIncident->id,
                'user_id' => null,
                'comment_id' => null,
            ]);
        } else if($action === 'resolved'){
            Notification::create([
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} marked the stranding incident as resolved.",
                'category' => 'general',
                'notif_for' => 'all',
                'type' => 'stranding',
                'is_read' => false,
                'created_at' => now(),
                'stranded_incident_id' => $strandedIncident->id,
                'user_id' => null,
                'comment_id' => null,
            ]);
        }else if($action === 'unresolved'){
            Notification::create([
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} updated the stranding incident as unresolved.",
                'category' => 'general',
                'notif_for' => 'all',
                'type' => 'stranding',
                'is_read' => false,
                'created_at' => now(),
                'stranded_incident_id' => $strandedIncident->id,
                'user_id' => null,
                'comment_id' => null,
            ]);
        } else if($action === 'archived'){
            Notification::create([
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} archived the reported stranding incident.",
                'category' => 'false',
                'notif_for' => 'responders',
                'type' => 'stranding',
                'is_read' => false,
                'created_at' => now(),
                'stranded_incident_id' => $strandedIncident->id,
                'user_id' => null,
                'comment_id' => null,
            ]);
        } else if($action === 'unarchived'){
            Notification::create([
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} unarchived an archived stranding incident.",
                'category' => 'general',
                'notif_for' => 'all',
                'type' => 'stranding',
                'is_read' => false,
                'created_at' => now(),
                'stranded_incident_id' => $strandedIncident->id,
                'user_id' => null,
                'comment_id' => null,
            ]);
        } else if($action === 'false'){
            Notification::create([
                'content' => "[{$userRole}] {$user->first_name} {$user->last_name} is already onsite and marked the reported stranding incident as false.",
                'category' => 'false',
                'notif_for' => 'all',
                'type' => 'stranding',
                'is_read' => false,
                'created_at' => now(),
                'stranded_incident_id' => $strandedIncident->id,
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
        $strandedIncident = StrandedIncident::with([
            'mediaFiles',
            'comments.user',
            'respondActions',
            'reportActions',
            'strandedSpecies',
            'user'
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
            'success' => session('success'),
            'municipalities' => Municipality::all(),
            'barangays' => Barangay::all()
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
        return Inertia::render('manage-stranded-incident/Update', [
            'strandedIncident' => $strandedIncident,
            'municipalities' => Municipality::all(),
            'barangays' => Barangay::all(),
        ]);
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
            'municipalities' => Municipality::all(),
            'barangays' => Barangay::all()
        ]);
    }

    public function update(Request $request, $id)
    {

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
            'detailed_location' => 'required|string',
            'more_information' => 'nullable|string',
            'municipality_id' => 'nullable|exists:municipalities,id',
            'barangay_id' => 'nullable|exists:barangays,id',
            'mediaFiles' => 'nullable|array',
            'mediaFiles.*' => 'mimes:jpeg,png,jpg,gif,svg,mp4,mov,avi,wmv|max:10240',
            'deletedImages' => 'nullable|array',
            'report_status' => 'nullable|in:pending,verified,completed,resolved,false'
        ]);

        $user = Auth::user();
        if ($user->user_role === 'public_user' && $request->report_status !== 'pending') {
            abort(403, 'Unauthorized action. The report is already reviewed by responders.');
        }

        $strandedIncident = StrandedIncident::findOrFail($id);

        $oldReportStatus = $strandedIncident->report_status;

        $strandedIncident->update($validated);

        // Check if the report status has changed
        if ($oldReportStatus !== $validated['report_status']) {
            // Call the createNotification method here
                    // Delete previous notifications if the status is marked as false
            if ($validated['report_status'] === 'false') {
                Notification::where('stranded_incident_id', $id)->delete();
                $this->handleFalseReport($strandedIncident->user_id);
            }

            $this->createNotification($strandedIncident, $validated['report_status']);

            if($validated['report_status'] === 'verified' || $validated['report_status'] === 'false'){
                $respondAction = RespondAction::where('stranded_incident_id', $strandedIncident->id)
                ->where('user_id', $user->id)
                ->first();

                // If a record exists, update it; otherwise, create a new record
                if ($respondAction) {
                // Update the existing record
                    $respondAction->update([
                        'response_status' => 'onsite',
                    ]);
                } else {
                // Create a new record
                    $respondAction = RespondAction::create([
                    'response_status' => 'onsite',
                    'stranded_incident_id' => $strandedIncident->id,
                    'user_id' => $user->id,
                    ]);
                }

                if($validated['report_status'] === 'verified'){
                    $successMessage = 'You have successfully verified a stranded incident report!';
                }
            }

        }else{
            $successMessage = 'You have successfully updated a stranded incident report!';
        }

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

        if($validated['report_status'] === 'false'){
            return redirect()->route('stranded.incident.index')
                        ->with('success', 'You have successfully marked a stranded incident report as false.');
        }

        // Redirect to the updated strandedIncident view with a success message
        return redirect()->route('stranded.incident.view', $id)
                        ->with('success', $successMessage);
    }

    // update report status
    public function complete($id)
    {
        $strandedIncident = StrandedIncident::findOrFail($id);

        // Check if there are any active stranded species forms
        $hasActiveSpeciesForms = $strandedIncident->strandedSpecies()
            ->where('is_active', true)
            ->exists();

        if (!$hasActiveSpeciesForms) {
            return back()->withErrors([
                'species_forms' => 'Cannot mark as complete. At least one detailed species form is required.'
            ]);
        }

        if ($strandedIncident->report_status === 'verified') {
            $strandedIncident->update(['report_status' => 'completed']);
            $this->createNotification($strandedIncident, 'completed');
            return redirect()->route('stranded.incident.view', $id)
                ->with('success', 'You have successfuly marked a stranded incident as complete.');
        } else {
            abort(400, 'Invalid report status.');
        }
    }

    public function resolve($id)
    {
        $strandedIncident = StrandedIncident::findOrFail($id);

        // Check if there are any active stranded species forms
        $hasActiveSpeciesForms = $strandedIncident->strandedSpecies()
            ->where('is_active', true)
            ->exists();

        if (!$hasActiveSpeciesForms) {
            return back()->withErrors([
                'species_forms' => 'Cannot mark as resolved. At least one detailed species form is required.'
            ]);
        }

        if ($strandedIncident->report_status === 'completed') {
            $strandedIncident->update(['report_status' => 'resolved']);
            $this->createNotification($strandedIncident, 'resolved');
            return redirect()->route('stranded.incident.index')
                ->with('success', 'You have successfully marked a stranded incident as resolved.');
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

        // Delete previous notifications related to this stranded incident
        Notification::where('stranded_incident_id', $id)->delete();

        $this->createNotification($strandedIncident, 'archived');

        return redirect()->route('stranded.incident.view', ['id' => $id])
            ->with('success', 'You have successfully archived a stranded incident report.');
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

        $this->createNotification($strandedIncident, 'unarchived');

        return redirect()->route('stranded.incident.view', ['id' => $id])
            ->with('success', 'You have successfully unarchived a stranded incident report.');
    }

    public function unresolve($id)
    {
        $strandedIncident = StrandedIncident::findOrFail($id);
        if($strandedIncident->report_status!=='resolved'){
            abort(403, 'Invalid');
        }
        $strandedIncident->report_status = 'completed';
        $strandedIncident->save();

        $this->createNotification($strandedIncident, 'unresolved');

        return redirect()->route('stranded.incident.view', ['id' => $id])
            ->with('success', 'You have successfully unresolved a stranded incident report.');
    }



    public function getStrandedIncidentStatus($id)
    {
        $strandedIncident = StrandedIncident::find($id);

        if (!$strandedIncident) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json(['status' => $strandedIncident->status]);
    }

    public function markAsFalse($id)
    {
        $strandedIncident = StrandedIncident::findOrFail($id);
        $strandedIncident->report_status = 'false';
        $strandedIncident->save();

        // Delete previous notifications
        Notification::where('stranded_incident_id', $id)->delete();

        // Create false report notification
        $this->createNotification($strandedIncident, 'false');

        // Handle false report for user
        $this->handleFalseReport($strandedIncident->user_id);

        return redirect()->route('stranded.incident.index')
            ->with('success', 'You have successfully marked a stranded incident report as false.');
    }
}
