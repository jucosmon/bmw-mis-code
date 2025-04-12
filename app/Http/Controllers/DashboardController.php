<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    //
    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return Inertia::render('dashboard/PublicUserDashboard');
        }else{
            $role = $user->user_role;

            if($role === 'barangay_official' || $role === 'bpemo_admin' ||
            $role === 'bpemo_staff' || $role === 'lgu_responder') {
                return Inertia::render('dashboard/ResponderDashboard');
            }else {
                return Inertia::render('dashboard/PublicUserDashboard');
            }
        }
    }
}
