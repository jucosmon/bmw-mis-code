<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use Illuminate\Http\Request;

class BarangayController extends Controller
{
    //
    public function index(Request $request)
    {
        $municipalityId = $request->query('municipality_id');
        return Barangay::where('municipality_id', $municipalityId)->get(); // Fetch barangays for the selected municipality
    }

}
