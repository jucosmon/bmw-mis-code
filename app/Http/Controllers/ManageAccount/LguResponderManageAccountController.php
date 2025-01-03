<?php

namespace App\Http\Controllers\ManageAccount;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Validation\Rules;

class LguResponderManageAccountController extends Controller
{
    public function index()
    {
        // Get the authenticated user's municipality
        $municipalityId = Auth::user()->municipality_id;

        // Fetch barangay officials within the same municipality
        $users = User::where('user_role', 'barangay_official')
                     ->where('municipality_id', $municipalityId)
                     ->get();

        return Inertia::render('manage-account/index', [
            'users' => $users,
            'type' => 'barangay_official'
        ]);
    }

    public function view($user_id)
    {
        // Fetch the user by ID
        $user = User::find($user_id);

        // Check if user exists and belongs to the same municipality
        if (!$user || $user->municipality_id !== Auth::user()->municipality_id) {
            return redirect()->route('lgu.responder.manage.account.index')->with('error', 'User not found or unauthorized.');
        }

        // Return the view with user data
        return Inertia::render('manage-account/View', ['user' => $user]);
    }

    public function createPage()
    {
        return Inertia::render('manage-account/Create', [
            'type' => 'barangay_official'
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        // Validate all required fields
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'contact_number' => 'nullable|string|min:11|max:15',
            'birthdate' => 'required|date',
            'sex' => 'required|in:male,female,other',
            'position' => 'required|string|max:100',
            'barangay_id' => 'required|integer|exists:barangays,id',
        ]);

        // Ensure the authenticated user can only assign their municipality
        $municipalityId = Auth::user()->municipality_id;

        // Create the user
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'contact_number' => $request->contact_number,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'user_role' => 'barangay_official',
            'position' => $request->position,
            'municipality_id' => $municipalityId,
            'barangay_id' => $request->barangay_id,
        ]);

        // Fire the Registered event
        event(new Registered(user: $user));

        return redirect()->route('lgu.responder.manage.account.index', ['type' => 'barangay_official']);
    }

    public function updatePage($user_id)
    {
        $user = User::find($user_id);

        // Check if user exists and belongs to the same municipality
        if (!$user || $user->municipality_id !== Auth::user()->municipality_id) {
            return redirect()->route('lgu.responder.manage.account.index')->with('error', 'User not found or unauthorized.');
        }

        return Inertia::render('manage-account/Update', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, $user_id)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user_id,
            'contact_number' => 'required|string|min:11',
            'birthdate' => 'required|date',
            'sex' => 'required|in:male,female,other',
            'position' => 'required|string|max:255',
            'barangay_id' => 'required|exists:barangays,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::findOrFail($user_id);

        if ($user->municipality_id !== Auth::user()->municipality_id) {
            return redirect()->route('lgu.responder.manage.account.index')->with('error', 'Unauthorized access.');
        }

        $user->update($request->only([
            'first_name', 'last_name', 'email', 'contact_number',
            'birthdate', 'sex', 'position', 'barangay_id'
        ]));

        return redirect()->route('lgu.responder.manage.account.view', ['user_id' => $user->id])
                        ->with('success', 'User account updated successfully.');
    }

    public function disable(Request $request, $user_id)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $currentUser = Auth::user();
        if (!Hash::check($request->password, $currentUser->password)) {
            return back()->withErrors(['password' => 'The provided password is incorrect.']);
        }

        $user = User::findOrFail($user_id);

        if ($user->municipality_id !== $currentUser->municipality_id || $user->id === $currentUser->id) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $user->is_active = false;
        $user->save();

        return redirect()->route('lgu.responder.manage.account.view', ['user_id' => $user->id])
                        ->with('success', 'You have successfully disabled the account!');
    }

    public function activate(Request $request, $user_id)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $currentUser = Auth::user();
        if (!Hash::check($request->password, $currentUser->password)) {
            return back()->withErrors(['password' => 'The provided password is incorrect.']);
        }

        $user = User::findOrFail($user_id);

        if ($user->municipality_id !== $currentUser->municipality_id || $user->id === $currentUser->id) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $user->is_active = true;
        $user->save();

        return redirect()->route('lgu.responder.manage.account.view', ['user_id' => $user->id])
                        ->with('success', 'You have successfully activated the account!');
    }
}
