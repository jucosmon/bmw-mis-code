<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserRestriction
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $currentUser = Auth::user();

        if ($currentUser && $currentUser->is_restricted) {
            if ($currentUser->restriction_end && Carbon::now()->gt($currentUser->restriction_end)) {
                $user = User::findOrFail($currentUser->id);
                $user->is_restricted = false;
                $user->restriction_start = null;
                $user->restriction_end = null;
                $user->save();
                return $next($request);
            }

            // User is still restricted
            Auth::logout();
            return redirect()->route('login')->with('error', 'Your account is restricted until ' .
                Carbon::parse($currentUser->restriction_end)->format('M d, Y h:i A'));
        }

        return $next($request);
    }
}
