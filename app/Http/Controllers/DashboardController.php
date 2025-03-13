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
            abort(403, 'Unauthorized action.');
        }

        // Determine the user's role and fetch the appropriate data and view
        $role = $user->user_role;

        if($role === 'barangay_official' || $role === 'bpemo_admin' ||
            $role === 'bpemo_staff' || $role === 'lgu_responder') {
                return Inertia::render('dashboard/ResponderDashboard');
        }else if($role === 'public_user') {
            return Inertia::render('dashboard/PublicUserDashboard');
        }else {
            abort(403, 'Unauthorized action.');
        }

    }

    /**
     * Prepare data and render the Barangay Official Dashboard.
     */
    // private function barangayOfficialDashboard($user)
    // {
    //     $data = [
    //         'specificData' => 'Barangay Official Data', // Replace with real data
    //         'user' => $user,
    //     ];

    //     return Inertia::render(component: 'dashboard/BarangayOfficialDashboard', $data);
    // }

    // /**
    //  * Prepare data and render the BPEMO Admin Dashboard.
    //  */
    // private function bpemoAdminDashboard($user)
    // {
    //     $data = [
    //         'specificData' => 'BPEMO Admin Data', // Replace with real data
    //         'user' => $user,
    //     ];

    //     return Inertia::render('dashboard/BpemoAdminDashboard', $data);
    // }

    // /**
    //  * Prepare data and render the BPEMO Staff Dashboard.
    //  */
    // private function bpemoStaffDashboard($user)
    // {
    //     $data = [
    //         'specificData' => 'BPEMO Staff Data', // Replace with real data
    //         'user' => $user,
    //     ];

    //     return Inertia::render('dashboard/BpemoStaffDashboard', $data);
    // }

    // /**
    //  * Prepare data and render the LGU Responder Dashboard.
    //  */
    // private function lguResponderDashboard($user)
    // {
    //     $data = [
    //         'specificData' => 'LGU Responder Data', // Replace with real data
    //         'user' => $user,
    //     ];

    //     return Inertia::render('dashboard/LguResponderDashboard', $data);
    // }

    // /**
    //  * Prepare data and render the Public User Dashboard.
    //  */
    // private function publicUserDashboard($user)
    // {
    //     $data = [
    //         'specificData' => 'Public User Data', // Replace with real data
    //         'user' => $user,
    //     ];

    //     return Inertia::render('dashboard/PublicUserDashboard', $data);
    // }
}
