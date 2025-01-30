<?php

namespace App\Http\Controllers;

use App\Models\Guideline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'title' => 'required',
            'description' => 'required',
            'user_role' => 'required',
        ]);

        $guideline = new Guideline();
        $guideline->title = $request->title;
        $guideline->description = $request->description;
        $guideline->user_role = $request->user_role;
        $guideline->is_active = true;
        $guideline->save();

        return redirect()->route('guideline.index')->with('success', 'Guideline created successfully');
    }

    public function updatePage($id)
    {
        $guideline = Guideline::find($id);
        return Inertia::render('manage-guideline/Edit', [
            'guideline' => $guideline,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'user_role' => 'required',
        ]);

        $guideline = Guideline::find($id);
        $guideline->title = $request->title;
        $guideline->description = $request->description;
        $guideline->user_role = $request->user_role;
        $guideline->save();

        return redirect()->route('guideline.index')->with('success', 'Guideline updated successfully');
    }

    public function view($id)
    {
        $guideline = Guideline::find($id)->with('mediaFiles')->first();
        return Inertia::render('manage-guideline/View', [
            'guideline' => $guideline,
            'success' => session('success'),
        ]);
    }

    public function archive($id)
    {
        $guideline = Guideline::find($id);
        $guideline->is_active = false;
        $guideline->save();

        return redirect()->route('guideline.index')->with('success', 'Guideline archived successfully');
    }

    public function unarchive($id)
    {
        $guideline = Guideline::find($id);
        $guideline->is_active = true;
        $guideline->save();

        return redirect()->route('guideline.index')->with('success', 'Guideline unarchived successfully');
    }


}
