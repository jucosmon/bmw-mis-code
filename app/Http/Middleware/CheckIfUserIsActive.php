<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckIfUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Check if the user is authenticated and if their account is active
        if ($user && !$user->is_active) {
            session()->flash('status', 'Invalid. Your account is currently disabled.');
            Auth::logout(); // Log out the user
            return redirect()->route('login', ['status' => 'Your account is currently disabled'])
            ->with('error', 'Your account has been deactivated.');
   }

        return $next($request);
    }
}
