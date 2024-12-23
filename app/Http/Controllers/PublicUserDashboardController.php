<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PublicUserDashboardController extends Controller
{
    //
    /**
     * Display the Public User Dashboard.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user(); // Use the Auth facade

        return Inertia::render('public-user/Dashboard', [
            'user' => $user,
        ]);
    }
}
