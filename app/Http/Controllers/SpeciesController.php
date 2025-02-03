<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\MediaFile;
use App\Models\Species;
use App\Models\SpeciesColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            'category'=>$category,
            'success' => session('success'),
            ]);
    }
    public function createPage($category)
    {
        return Inertia::render('manage-species/Create', [
            'category' => $category,
            'colors' => Color::all(), // Pass all colors to the view
        ]);
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
            'colors' => 'nullable|array',
            'colors.*' => 'string|exists:colors,name',
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

        if ($request->has('colors')) {
            foreach ($request->colors as $colorName) {
                $color = Color::where('name', $colorName)->firstOrFail();
                SpeciesColor::create([
                    'species_id' => $species->id,
                    'color_id' => $color->id,
                ]);
            }
        }

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

        return redirect()->route('bpemo.admin.manage.species.index', [$category])
        ->with('success', 'You have successfully created a species!');
    }

    public function view($id)
    {
        $species = Species::with(['mediaFiles', 'speciesColors.color'])->findOrFail($id);

        // Map media files to include public URLs
        $species->mediaFiles = $species->mediaFiles->map(function ($file) {
            $file->url = asset('storage/' . $file->path);
            return $file;
        });

        // Map speciesColors to include color names
        $species->speciesColors = $species->speciesColors->map(function ($speciesColor) {
            $color = Color::findOrFail($speciesColor->color_id);
            $speciesColor->color_name = $color->name;
            return $speciesColor;
        });

        return Inertia::render('manage-species/View', [
            'species' => $species,
            'success' => session('success'),
        ]);
    }


    public function updatePage($id)
    {
        $species = Species::with('speciesColors.color')->findOrFail($id);

        // Load media files and include public URLs
        $species->load('mediaFiles');
        $species->mediaFiles = $species->mediaFiles->map(function ($file) {
        $file->url = asset('storage/' . $file->path);
            return $file;
        });


        // Map speciesColors to include color names
        $species->speciesColors = $species->speciesColors->map(function ($speciesColor) {
            $color = Color::findOrFail($speciesColor->color_id);
            $speciesColor->color_name = $color->name;
            return $speciesColor;
        });

        return Inertia::render('manage-species/Update', [
            'species' => $species,
            'colors' => Color::get(),
        ]);
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
            'colors' => 'nullable|array',
            'colors.*' => 'string|exists:colors,name',
            'deletedColors' => 'nullable|array'
        ]);

        // Find species to update
        $species = Species::findOrFail($id);
        $species->update($validated);

        // Handle deleted colors
        if ($request->has('deletedColors')) {
            foreach ($request->deletedColors as $deletedColorName) {
                $color = Color::where('name', $deletedColorName)->firstOrFail();
                SpeciesColor::where('species_id', $species->id)->where('color_id', $color->id)->delete();
            }
        }

        // Handle new colors
        if ($request->has('colors')) {
            foreach ($request->colors as $colorName) {
                $color = Color::where('name', $colorName)->firstOrFail();
                // Check if the color already exists for this species to avoid duplicates
                if (!SpeciesColor::where('species_id', $species->id)->where('color_id', $color->id)->exists()) {
                    SpeciesColor::create([
                        'species_id' => $species->id,
                        'color_id' => $color->id,
                    ]);
                }
            }
        }

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
                    $path = $file->store('species', 'public');
                    $species->mediaFiles()->create([
                        'path' => $path,
                        'name' => $file->getClientOriginalName(),
                        'file_for' => 'species',
                        'type' => $file->getClientMimeType(),
                        'species_id' => $species->id,
                    ]);
                }
            }
        }

        // Redirect to the updated species view with a success message
        return redirect()->route('bpemo.admin.manage.species.view', $id)
                        ->with('success', 'You have successfully updated a species!');
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

        return redirect()->route('bpemo.admin.manage.species.view', ['id' => $id, 'message'=> 'Successfully Archived account'])
        ->with('success', 'You have successfully archived a species!');
    }

    public function unarchive(Request $request, $category, $id)
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
        $species->is_active = true;
        $species->save();

        return redirect()->route('bpemo.admin.manage.species.view', ['id' => $id, 'message'=> 'Successfully Archived account'])
        ->with('success', 'You have successfully unarchived a species!');
    }

    public function indexExploreSpecies()
    {
        // Fetch top 5 species commonly involved in active sightings
        $topSpecies = Species::withCount(['sightedSpecies' => function ($query) {
            $query->whereHas('sighting', function ($query) {
                $query->where('is_active', true);
            });
        }])
        ->orderBy('sighted_species_count', 'desc')
        ->take(5)
        ->get();

        // Define categories
        $categories = ['Marine Turtles', 'Marine Mammals', 'Sharks and Rays'];
        $colors = Color::get();

        return Inertia::render('manage-species/explore-species/index', [
            'topSpecies' => $topSpecies,
            'categories' => $categories,
            'colors' => $colors,
            'success' => session('success'),
        ]);
    }
}
