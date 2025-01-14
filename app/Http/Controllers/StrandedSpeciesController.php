<?php

namespace App\Http\Controllers;

use App\Models\Species;
use App\Models\StrandedIncident;
use App\Models\StrandedSpecies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class StrandedSpeciesController extends Controller
{
    //
    public function createPage($id)
    {
        $strandedIncident = StrandedIncident::findOrFail($id);
        $species = Species::get();
        return Inertia::render('manage-stranded-incident/stranded-species/Create', [
            'strandedIncident' => $strandedIncident,
            'species' => $species,
        ]);
    }

    public function create(Request $request, $id)
    {
        $user = Auth::user();

        // Validate the incoming request
        $request->validate([
            'condition_code' => 'required|numeric|min:1|max:6',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'sex' => 'required|in:male,female,unknown',
            'length' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
            'girth' => 'nullable|numeric',
            'disposition' => 'nullable|string',
            'disposal_site' => 'nullable|string',
            'more_information' => 'nullable|string',
            'is_released' => 'required|boolean',
            'species_id' => 'nullable|exists:species,id'
        ]);

        // Create the strandedSpecies
        $strandedSpeciesData = [
            'condition_code' => $request->condition_code,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'sex' => $request->sex,
            'length' => $request->length,
            'weight' => $request->weight,
            'girth' => $request->girth,
            'disposition' => $request->disposition,
            'disposal_site' => $request->disposal_site,
            'more_information' => $request->more_information,
            'is_released' => $request->is_released,
            'is_active' => true,
            'species_id' => $request->species_id,
            'stranded_incident_id' => $id,
            'user_id' => $user->id,
        ];


        // Create the strandedSpecies record
        StrandedSpecies::create($strandedSpeciesData);

        return redirect()->route('stranded.incident.view', $id)->with('success', 'Stranded Incident created successfully!');
    }

    public function view($id)
    {
        $strandedSpecies = StrandedSpecies::with('strandedIncident')->findOrFail($id);

        $species = Species::find($strandedSpecies->species_id);
        $strandedSpecies->species_name = $species ? $species->name : 'Unknown Species';

        return Inertia::render('manage-stranded-incident/stranded-species/View', [
            'strandedSpecies' => $strandedSpecies,
            'strandedIncident' => $strandedSpecies->strandedIncident, // Access the related strandedIncident
        ]);
    }

    public function updatePage($id)
    {
        $strandedSpecies = StrandedSpecies::findOrFail($id);
        $species = Species::get();
        return Inertia::render('manage-stranded-incident/stranded-species/Update',
        ['strandedSpecies' => $strandedSpecies,
        'species' => $species
    ]);
    }
    public function update(Request $request, $id)
    {
        // Validate incoming data
        $validated = $request->validate([
            'condition_code' => 'required|numeric|min:1|max:6',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'sex' => 'required|in:male,female,unknown',
            'length' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
            'girth' => 'nullable|numeric',
            'disposition' => 'nullable|string',
            'disposal_site' => 'nullable|string',
            'more_information' => 'nullable|string',
            'is_released' => 'required|boolean',
            'species_id' => 'nullable|exists:species,id'
        ]);

        $strandedSpecies = StrandedSpecies::findOrFail($id);
        $strandedSpecies->update($validated);

        return redirect()->route('stranded.species.view', $id)
            ->with('success', 'Stranded Species updated successfully.');
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

        $strandedSpecies = StrandedSpecies::findOrFail($id);
        $strandedSpecies->is_active = false;
        $strandedSpecies->save();

        return redirect()->route('stranded.incident.view', ['id' => $strandedSpecies->stranded_incident_id, 'message'=> 'Successfully archived stranded species report'])->with('success', 'Stranded species archived successfully.');
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

        $strandedSpecies = StrandedSpecies::findOrFail($id);
        $strandedSpecies->is_active = true;
        $strandedSpecies->save();

        return redirect()->route('stranded.species.view', ['id' => $id, 'message'=> 'Successfully unarhived stranded species'])->with('success', 'Stranded incident unarchived successfully.');
    }
}
