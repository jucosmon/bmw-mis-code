<?php

namespace App\Http\Controllers;

use App\Models\Guideline;
use App\Models\Item;
use App\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GuidelineController extends Controller
{
    //
    public function index()
    {
        Auth::user();

        switch(Auth::user()->user_role ){
            case 'bpemo_admin':
                 $guidelines = Guideline::where('is_active' , true)->get(); break;
            case 'lgu_responder':
                $guidelines = Guideline::where('user_role', 'lgu_responder')
                ->where('is_active' , true)->get();
            case 'barangay_official':
                $guidelines = Guideline::where('user_role', 'barangay_official')
                ->where('is_active' , true)->get();
            case 'public_user':
                $guidelines = Guideline::where('user_role', 'public_user')
                ->where('is_active' , true)->get();
            default: abort(403, 'Unauthorized action.');
        }

        return Inertia::render('manage-guideline/index', [
            'guidelines' => $guidelines,
            'success' => session('success'),
        ]);
    }

    public function createPage()
    {
        return Inertia::render('manage-guideline/Create');
    }

    public function create(Request $request)
    {
        $request->validate([
            'title' => 'string|required|max:100',
            'description' => 'string|required',
            'user_role' => 'required|in:lgu_responder,barangay_official,public_user',
            'category' => 'required|in:marine_turtles,marine_mammals,sharks_rays',
            'items' => 'required|array|min:1',
            'items.*.count' => 'required|integer|min:1',
            'items.*.text' => 'required|string',
            'mediaFiles' => 'nullable|array',
            'mediaFiles.*' => 'file|mimes:jpeg,png,jpg,svg,mp4,mov,avi,wmv,mkv,doc,docx,pdf,ppt,pptx,xls,xlsx|max:10240', // Expanded file types
        ]);

        $guideline = Guideline::create([
            'title' => $request->title,
            'description' => $request->description,
            'user_role' => $request->user_role,
            'is_active' => true
        ]);

        foreach ($request->items as $item) {
            $createdItem = $guideline->items()->create([
                'count' => $item['count'],
                'text' => $item['text'],
                'guideline_id' => $guideline->id,
            ]);

            if ($request->hasFile('mediaFiles')) {
                foreach ($request->file('mediaFiles') as $mediaFile) {
                    $path = $mediaFile->store('item', 'public');

                    MediaFile::create([
                        'path' => $path,
                        'name' => $mediaFile->getClientOriginalName(),
                        'file_for' => 'item',
                        'type' => $mediaFile->getClientMimeType(),
                        'item_id' => $createdItem->id, // Use the ID of the created item
                    ]);
                }
            }
        }

        return redirect()->route('guideline.index')->with('success', 'Guideline created successfully');
    }

    public function updatePage($id)
    {
        $guideline = Guideline::with('mediaFiles', 'items')->find($id);
        return Inertia::render('manage-guideline/Update', [
            'guideline' => $guideline,
        ]);
    }

    public function update(Request $request, $id)
{
    $validated = $request->validate([
        'title' => 'string|required|max:100',
        'description' => 'string|required',
        'user_role' => 'required|in:lgu_responder,barangay_official,public_user',
        'category' => 'required|in:marine_turtles,marine_mammals,sharks_rays',
        'items' => 'required|array|min:1',
        'items.*.count' => 'required|integer|min:1',
        'items.*.text' => 'required|string',
        'mediaFiles' => 'nullable|array',
        'mediaFiles.*' => 'nullable|file|mimes:jpeg,png,jpg,svg,mp4,mov,avi,wmv,mkv,doc,docx,pdf,ppt,pptx,xls,xlsx|max:10240',
        'deletedMediaIds' => 'nullable|array',
        'deletedItems' => 'nullable|array',
    ]);

    $guideline = Guideline::findOrFail($id);
    $guideline->update($validated);

    // Handle deleted Items
    if ($request->has('deletedItems')) {
        $deletedItemsIds = $request->input('deletedItems');
        foreach ($deletedItemsIds as $deletedItemsId) {
            $item = Item::find($deletedItemsId);
            if ($item) {
                $item->delete();
            } else {
                 Log::error('Item not found for ID: ' . $deletedItemsId);
            }
        }
    }

    // Handle updated Items
    foreach ($validated['items'] as $item) {
        if (isset($item['id']) && !is_null($item['id'])) {
            // Update existing item
            $itemModel = Item::findOrFail($item['id']);
            $itemModel->update($item);

            // Handle media file deletion
            if ($request->has('deletedMediaIds')) {
                $deletedMediaIds = $request->input('deletedMediaIds');
                foreach ($deletedMediaIds as $deletedMediaId) {
                    $media = MediaFile::findOrFail($deletedMediaId);
                    Storage::disk('public')->delete($media->path);
                    $media->delete();
                }
            }

            // Handle new media files upload
            if ($request->hasFile('mediaFiles')) {
                foreach ($request->file('mediaFiles') as $file) {
                    if ($file->isValid()) {
                        $path = $file->store('item', 'public');
                        $itemModel->mediaFiles()->create([
                            'path' => $path,
                            'name' => $file->getClientOriginalName(),
                            'file_for' => 'item',
                            'type' => $file->getClientMimeType(),
                            'item_id' => $itemModel->id,
                        ]);
                    }
                }
            }
        } else {
            // Create new item
            $createdItem = $guideline->items()->create([
                'count' => $item['count'],
                'text' => $item['text'],
                'guideline_id' => $guideline->id,
            ]);

            // Handle new media files upload for the new item
            if ($request->hasFile('mediaFiles')) {
                foreach ($request->file('mediaFiles') as $mediaFile) {
                    if ($mediaFile->isValid()) {
                        $path = $mediaFile->store('item', 'public');
                        MediaFile::create([
                            'path' => $path,
                            'name' => $mediaFile->getClientOriginalName(),
                            'file_for' => 'item',
                            ' type' => $mediaFile->getClientMimeType(),
                            'item_id' => $createdItem->id,
                        ]);
                    }
                }
            }
        }
    }

    return redirect()->route('guideline.index')->with('success', 'Guideline updated successfully');
}

    public function view($id)
    {
        $guideline = Guideline::findOrFail($id)->with('mediaFiles', 'items')->first();
        return Inertia::render('manage-guideline/View', [
            'guideline' => $guideline,
            'success' => session('success'),
        ]);
    }

    public function archive($id)
    {
        $guideline = Guideline::findOrFail($id);
        $guideline->is_active = false;
        $guideline->save();

        return redirect()->route('guideline.index')->with('success', 'Guideline archived successfully');
    }

    public function unarchive($id)
    {
        $guideline = Guideline::findOrFail($id);
        $guideline->is_active = true;
        $guideline->save();

        return redirect()->route('guideline.index')->with('success', 'Guideline unarchived successfully');
    }

}
