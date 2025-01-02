<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use App\Models\Species;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class SpeciesController extends Controller
{
    //
    public function index($category)
    {
        $species = Species::where('category', $category)
                          ->with('mediaFiles')
                          ->get();

        if ($species->isEmpty()) {
            return Inertia::render('manage-species/index',
            ['species' => [],
            'category' => $category,
            'message' => 'No species found for this category.']
        );}

        return Inertia::render('manage-species/index',
            ['species' => $species,
            'category'=>$category
            ]);
    }
    public function createPage($category)
    {
        return Inertia::render('manage-species/Create',
        ['category' => $category]);
    }

    public function create(Request $request, $category)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'scientific_name' => 'nullable|string|max:100',
            'common_name' => 'nullable|string|max:100',
            'local_name' => 'nullable|string|max:100',
            'description' => 'required|string',
            'conservation_status' => 'required|in:CR,NT,EN,DD,VU,NA,LC',
            'max_size' => 'nullable|numeric',
            'shape' => 'required|in:turtle-like,shark-like,dolphin-like,dugong-like,whale-like,ray-like',
            'is_dangerous' => 'nullable|boolean',
            'mediaFiles' => 'nullable|array',
            'mediaFiles.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);

        // Create the species
        $species = Species::create([
            'name' => $request->name,
            'scientific_name' => $request->scientific_name,
            'common_name' => $request->common_name,
            'local_name' => $request->local_name,
            'category' => $category,
            'description' => $request->description,
            'conservation_status' => $request->conservation_status,
            'max_size' => $request->max_size,
            'shape' => $request->shape,
            'is_dangerous' => $request->is_dangerous,
            'is_active' => true,
        ]);

        // Handle file uploads
        if ($request->hasFile('mediaFiles')) {
            foreach ($request->file('mediaFiles') as $mediaFile) {
                $path = $mediaFile->store('species', 'public');

                MediaFile::create([
                    'path' => $path,
                    'name' => $mediaFile->getClientOriginalName(),
                    'file_for' => 'species',
                    'type' => $mediaFile->getClientMimeType(),
                    'species_id' => $species->id,
                ]);
            }
        }

        return redirect()->route('bpemo.admin.manage.species.index', [$category])->with('success', 'Species created successfully!');
    }

    public function view($id)
    {
        $species = Species::with('mediaFiles')->findOrFail($id);

            // Map media files to include public URLs
            $species->mediaFiles = $species->mediaFiles->map(function ($file) {
                $file->url = asset('storage/' . $file->path);
                return $file;
            });

        return Inertia::render('manage-species/View', ['species' => $species]);
    }

    public function updatePage($id)
    {
        $species = Species::findOrFail($id);
        return Inertia::render('manage-species/Update', ['species' => $species]);
    }

    public function update(Request $request, $id)
    {
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
            'is_active' => 'nullable|boolean',
        ]);

        $species = Species::findOrFail($id);
        $species->update($validated);

        return redirect()->route('bpemo.admin.manage.species.view', $id)->with('success', 'Species updated successfully.');
    }

    public function archive(Request $request, $category, $id)
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

        $species = Species::findOrFail($id);
        $species->is_active = false;
        $species->save();

        return redirect()->route('bpemo.admin.manage.species.view', ['id' => $id, 'message'=> 'Successfully Archived account'])->with('success', 'Species archived successfully.');
    }
}
