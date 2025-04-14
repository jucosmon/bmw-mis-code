<?php

namespace App\Http\Controllers;

use App\Models\Guideline;
use App\Models\Item;
use App\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GuidelineController extends Controller
{
    public function indexForBasicUser (){
        $user = Auth::user();

        $guidelines = Guideline::where('user_role', $user ? $user->user_role : 'public_user')
            ->where('is_active', true)
            ->get();

        return Inertia::render('manage-guideline/basic-users/index', [
            'guidelines' => $guidelines,
        ]);
    }

    public function viewForBasicUser ($id)
{
    $user = Auth::user();

    // Fetch the guideline with its items and media files
    $guideline = Guideline::with(['items'])->findOrFail($id);

    if($user){
        if ($guideline->user_role !== $user->user_role) {
            abort(403, 'You cannot access a guideline for this user type');
        }
    }else{
        if($guideline->user_role !== 'public_user'){
            abort(403, 'You cannot access a guideline for this user type');
        }
    }


    foreach ($guideline->items as $item) {
        $item->mediaFiles = MediaFile::where('item_id', $item->id)->get();
    }


    return Inertia::render('manage-guideline/basic-users/view', [
        'guideline' => $guideline,
    ]);
}

    //for bpemo admin
    public function index($user_role)
    {
        $guidelines = [];

        switch ($user_role) {
            case 'lgu_responder':
                $guidelines = Guideline::where('user_role', 'lgu_responder')->get();
                break;
            case 'barangay_official':
                $guidelines = Guideline::where('user_role', 'barangay_official')->get();
                break;
            case 'public_user':
                $guidelines = Guideline::where('user_role', 'public_user')->get();
                break;
            default:
                abort(403, 'Invalid role.');
        }

        return Inertia::render('manage-guideline/index', [
            'guidelines' => $guidelines,
            'success' => session('success'),
            'user_role' => $user_role,
        ]);
    }

    public function view($id)
    {
        // Fetch the guideline with its items
        $guideline = Guideline::with(['items'])->findOrFail($id);

        foreach ($guideline->items as $item) {
            $item->mediaFiles = MediaFile::where('item_id', $item->id)->get();
        }

        // Render the view with the guideline data
        return Inertia::render('manage-guideline/View', [
            'guideline' => $guideline,
            'success' => session('success'),
        ]);
    }

    public function createPage($user_role)
    {
        return Inertia::render('manage-guideline/Create', [
            'user_role' => $user_role,
        ]);

    }

    public function create(Request $request, $user_role)
    {
        $request->validate([
            'title' => 'string|required|max:100',
            'description' => 'string|required',
            'user_role' => 'required|in:lgu_responder,barangay_official,public_user',
            'category' => 'required|in:marine_turtles,marine_mammals,sharks_rays',
            'language' => 'required|in:english,filipino,bisaya',
            'items' => 'required|array|min:1',
            'items.*.count' => 'required|integer|min:1',
            'items.*.text' => 'required|string',
            'mediaFiles' => 'nullable|array',
            'mediaFiles.*' => 'file|mimes:jpeg,png,jpg,svg,mp4,mov,avi,wmv,mkv,doc,docx,pdf,ppt,pptx,xls,xlsx|max:25024',
        ]);

        $guideline = Guideline::create([
            'title' => $request->title,
            'description' => $request->description,
            'user_role' => $request->user_role,
            'category' => $request->category,
            'language' => $request->language,
            'is_active' => true
        ]);

        foreach ($request->items as $item) {
            $createdItem = $guideline->items()->create([
                'count' => $item['count'],
                'text' => $item['text'],
                'guideline_id' => $guideline->id,
            ]);

            // Check if mediaFiles exist for the current item
            if (isset($item['mediaFiles'])) {
                foreach ($item['mediaFiles'] as $mediaFile) {
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

        return redirect()->route('manage.guideline.index', [
            'user_role' => $user_role,
            'archived' => 'false',
            ])->with('success', 'Guideline created successfully');
    }

    public function updatePage($id)
    {
        // Eager load items and their mediaFiles
        $guideline = Guideline::with(['items.mediaFiles'])->findOrFail($id);

        // Create a separate property for mediaFiles
        $itemsWithMediaFiles = $guideline->items->map(function ($item) {
            return [
                'id' => $item->id,
                'count' => $item->count,
                'text' => $item->text,
                'guideline_id' => $item->guideline_id,
                'mediaFiles' => $item->mediaFiles,
            ];
        });

        return Inertia::render('manage-guideline/Update', [
            'guideline' => $guideline,
            'itemsWithMediaFiles' => $itemsWithMediaFiles,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'string|required|max:100',
            'description' => 'string|required',
            'user_role' => 'required|in:lgu_responder,barangay_official,public_user',
            'category' => 'required|in:marine_turtles,marine_mammals,sharks_rays',
            'language' => 'required|in:english,filipino,bisaya',
            'items' => 'required|array|min:1',
            'items.*.count' => 'required|integer|min:1',
            'items.*.text' => 'required|string',
            'items.*.id' => 'nullable|integer',
            'items.*.deletedFiles' => 'nullable|array',
            'items.*.mediaFiles' => 'nullable|array',
            'deletedItems' => 'nullable|array',
        ]);

        // Handle deleted Items
        if ($request->has('deletedItems') && !empty($request->input('deletedItems'))) {
            foreach ($request->input('deletedItems') as $deletedItemsId) {
                $item = Item::find($deletedItemsId);
                if ($item) {
                    // Delete associated media files for the item in the storage
                    if ($item->mediaFiles) {
                        foreach ($item->mediaFiles as $mediaFile) {
                            Storage::disk('public')->delete($mediaFile->path);
                            $mediaFile->delete();
                            Log::info('Deleted media file with path: ' . $mediaFile->path);
                        }
                    }

                    // Delete the item itself
                    $item->delete();
                    Log::info('Deleted item with ID: ' . $deletedItemsId);
                } else {
                    Log::error('Item not found for ID: ' . $deletedItemsId);
                }
            }
        }

        $guideline = Guideline::findOrFail($id);
        $guideline->update($validated);

        // Handle updated Items
        foreach ($validated['items'] as $item) {
            if (isset($item['id']) && !is_null($item['id'])) {
                // Update existing item
                Log::info('Updating item with ID: ' . $item['id']);
                $itemModel = Item::findOrFail($item['id']);
                $itemModel->update($item);

                // Handle media file deletion
                if (isset($item['deletedFiles']) && !empty($item['deletedFiles'])) {
                    foreach ($item['deletedFiles'] as $deletedMediaId) {
                        $media = MediaFile::findOrFail($deletedMediaId);
                        Storage::disk('public')->delete($media->path);
                        $media->delete();
                        Log::info('Deleted media file with ID: ' . $deletedMediaId);
                    }
                }

                // Handle new media files upload for the existing item
                if (isset($item['mediaFiles']) && is_array($item['mediaFiles'])) {
                    foreach ($item['mediaFiles'] as $file) {
                        if ($file instanceof UploadedFile && $file->isValid()) {
                            $path = $file->store('item', 'public');
                            $itemModel->mediaFiles()->create([
                                'path' => $path,
                                'name' => $file->getClientOriginalName(),
                                'file_for' => 'item',
                                'type' => $file->getClientMimeType(),
                                'item_id' => $itemModel->id,
                            ]);
                            Log::info('Added new media file for item ID: ' . $itemModel->id);
                        } else {
                            Log::error('Invalid file upload for item ID: ' . $itemModel->id);
                        }
                    }
                }
            } else {
                // Create new item
                Log::info('Creating a new item');
                $createdItem = $guideline->items()->create([
                    'count' => $item['count'],
                    'text' => $item['text'],
                    'guideline_id' => $guideline->id,
                ]);

                // Handle new media files upload for the new item
                if (isset($item['mediaFiles']) && is_array($item['mediaFiles'])) {
                    foreach ($item['mediaFiles'] as $mediaFile) {
                        if ($mediaFile instanceof UploadedFile && $mediaFile->isValid()) {
                            $path = $mediaFile->store('item', 'public');
                            MediaFile::create([
                                'path' => $path,
                                'name' => $mediaFile->getClientOriginalName(),
                                'file_for' => 'item',
                                'type' => $mediaFile->getClientMimeType(),
                                'item_id' => $createdItem->id,
                            ]);
                            Log::info('Added new media file for newly created item ID: ' . $createdItem->id);
                        } else {
                            Log::error('Invalid file upload for newly created item ID: ' . $createdItem->id);
                        }
                    }
                }
            }
        }

        return redirect()->route('manage.guideline.view', [
            'id' => $guideline->id,
        ])->with('success', 'Guideline updated successfully');
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

        $guideline = Guideline::findOrFail($id);
        $guideline->is_active = false;
        $guideline->save();

        return redirect()->route('manage.guideline.index', [
            'user_role' => $guideline->user_role,
            'archived' => 'false',
        ])->with('success', 'Guideline archived successfully');
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

        $guideline = Guideline::findOrFail($id);
        $guideline->is_active = true;
        $guideline->save();

        return redirect()->back()->with('success', 'Guideline unarchived successfully');
    }

}
