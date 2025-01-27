<?php

namespace App\Http\Controllers;

use App\Models\SightedSpecies;
use App\Models\Sighting;
use App\Models\Species;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class SightedSpeciesController extends Controller
{
    //
    public function createPage($id)
    {
        $sighting = Sighting::findOrFail($id);
        $species = Species::get();
        return Inertia::render('manage-sighting/sighted-species/Create', [
            'sighting' => $sighting,
            'species' => $species,
        ]);
    }

    public function create(Request $request, $id)
    {
        $user = Auth::user();

        // Validate the incoming request
        $request->validate([
            'size' => 'enum:tiny,small,medium,large,very_large,giant',
            'species_description' => 'nulllable|string',
            'behavior_observed' => 'string',
            'species_id' => 'nullable|exists:species,id'
        ]);

        // Create the sightedSpecies
        $sightedSpeciesData = [
            'size' => $request->size,
            'species_description' => $request->species_description,
            'behavior_observed' => $request->behavior_observed,
            'species_id' => $request->species_id,
            'sighting_id' => $id,
        ];


        // Create the sightedSpecies record
        SightedSpecies::create($sightedSpeciesData);

        return redirect()->route('sighting.view', $id)->with('success', 'Sighted Species created successfully!');
    }

    public function view($id)
    {
        $sightedSpecies = SightedSpecies::with('sighting')->findOrFail($id);

        $species = Species::find($sightedSpecies->species_id);
        $sightedSpecies->species_name = $species ? $species->name : 'Unknown Species';

        return Inertia::render('manage-sighting/sighted-species/View', [
            'sightedSpecies' => $sightedSpecies,
            'sighting' => $sightedSpecies->sighting, // Access the related sighting
        ]);
    }

    public function updatePage($id)
    {
        $sightedSpecies = SightedSpecies::findOrFail($id);
        $species = Species::get();
        return Inertia::render('manage-sighting/sighted-species/Update',[
            'sightedSpecies' => $sightedSpecies,
            'species' => $species
    ]);
    }
    public function update(Request $request, $id)
    {
        // Validate incoming data
        $validated = $request->validate([
            'size' => 'enum:tiny,small,medium,large,very_large,giant',
            'species_description' => 'nullable|string',
            'behavior_observed' => 'string',
            'species_id' => 'nullable|exists:species,id'
        ]);

        $sightedSpecies = SightedSpecies::findOrFail($id);
        $sightedSpecies->update($validated);

        return redirect()->route('sighted.species.view', $id)
            ->with('success', 'Sighted Species updated successfully.');
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

        $sightedSpecies = SightedSpecies::findOrFail($id);
        $sightedSpecies->is_active = false;
        $sightedSpecies->save();

        return redirect()->route('sighting.view', ['id' => $sightedSpecies->sighting_id, 'message'=> 'Successfully archived sighted species report'])->with('success', 'sighted species archived successfully.');
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

        $sightedSpecies = SightedSpecies::findOrFail($id);
        $sightedSpecies->is_active = true;
        $sightedSpecies->save();

        return redirect()->route('sighted.species.view', ['id' => $id, 'message'=> 'Successfully unarhived sighted species'])->with('success', 'Sighted Species unarchived successfully.');
    }
}
