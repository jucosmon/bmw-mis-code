<?php

namespace App\Http\Controllers;

use App\Models\Species;
use App\Models\StrandedIncident;
use App\Models\StrandedSpecies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
}
