<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BpemoAdminDashboardController extends Controller
{
    /**
     * Display the BPemo Admin Dashboard.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user(); // Use the Auth facade

        return Inertia::render('bpemo-admin/Dashboard', [
            'user' => $user,
        ]);
    }

}
