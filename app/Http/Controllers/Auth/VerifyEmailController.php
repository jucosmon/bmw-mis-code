<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        try {
            $user = User::find($request->route('id'));

            if (!$user) {
                Log::error('Email verification failed: User not found', ['id' => $request->route('id')]);
                return redirect()->route('login')->with('error', 'Invalid verification link.');
            }

            if ($user->hasVerifiedEmail()) {
                return redirect()->route('login')->with('status', 'Email already verified.');
            }

            $request->fulfill();

            // Log the user in after verification
            Auth::login($user);

            return redirect()->route('dashboard')->with('status', 'Email verified successfully!');
        } catch (\Exception $e) {
            Log::error('Email verification failed: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Verification failed. Please try again.');
        }
    }
}
