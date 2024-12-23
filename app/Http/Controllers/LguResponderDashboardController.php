<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LguResponderDashboardController extends Controller
{
    //
    /**
     * Display the LGU Responder Dashboard.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user(); // Use the Auth facade

        return Inertia::render('lgu-responder/Dashboard', [
            'user' => $user,
        ]);
    }
}
