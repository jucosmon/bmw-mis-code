<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Authenticate the user
        $request->authenticate();

        // Regenerate the session to prevent session fixation attacks
        $request->session()->regenerate();

        // Redirect based on user role
        return $this->redirectUserBasedOnRole();
    }

    /**
     * Redirect the user based on their role after login.
     */
    protected function redirectUserBasedOnRole(): RedirectResponse
    {
        $user = Auth::user(); // Get the authenticated user


        // Redirect based on user role
        switch ($user->user_role) {
            case 'bpemo_admin':
                return redirect()->route('bpemo.admin.dashboard');
            case 'bpemo_staff':
                return redirect()->route('bpemo.staff.dashboard');
            case 'lgu_responder':
                return redirect()->route('lgu.responder.dashboard');
            case 'barangay_official':
                return redirect()->route('barangay.official.dashboard');
            case 'public_user':
                return redirect()->route('public.user.dashboard');
            default:
                return redirect()->route('unauthorized'); // Fallback if role is not matched
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
