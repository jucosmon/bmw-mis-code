<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BpemoStaffDashboardController extends Controller
{
    //
    /**
     * Display the BPEMO STaff Dashboard.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user(); // Use the Auth facade

        return Inertia::render('bpemo-staff/Dashboard', [
            'user' => $user,
        ]);
    }
}
