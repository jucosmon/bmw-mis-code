<?php

namespace App\Http\Controllers\ManageAccount;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Validation\Rules;


class BpemoAdminManageAccountsController extends Controller
{
    //
    public function index($type){

        // Validate if the type is a valid user role
        $validRoles = ['bpemo_admin', 'bpemo_staff', 'lgu_responder', 'barangay_official', 'public_user'];

        if (!in_array($type, $validRoles)) {
            abort(404, 'Invalid user role');
        }

        $users = User::where('user_role', $type)->get();

        return Inertia::render('manage-account/index', [
            'users' => $users,
            'type' => $type
        ]);
    }

    public function view($user_id)
    {
        // Fetch the user by ID
        $user = User::find($user_id);

        // Check if user exists
        if (!$user) {
            return redirect()->route('bpemo.admin.manage.account.index')->with('error', 'User  not found.');
        }

        // Return the view with user data
        return Inertia::render('manage-account/View', ['user' => $user ]);
    }


    public function createPage($type){
        $validRoles = ['bpemo_admin', 'bpemo_staff', 'lgu_responder', 'barangay_official'];

        if (!in_array($type, $validRoles)) {
            abort(404, 'Invalid user role');
        }

        return Inertia::render('manage-account/Create',[
            'type' =>$type
        ]);
    }

    public function create(Request $request, $type): RedirectResponse
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
            'municipality_id' => 'required|integer|exists:municipalities,id',
            'barangay_id' => 'required|integer|exists:barangays,id',
        ]);

        // Create the user with all fields
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'contact_number' => $request->contact_number,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'user_role' => $type,
            'position' => $request->position,
            'municipality_id' => $request->municipality_id,
            'barangay_id' => $request->barangay_id,
        ]);

        // Fire the Registered event
        event(new Registered(user: $user));
        $user->sendEmailVerificationNotification();

        // Redirect to the manage account index with the user type
        return redirect()->route('bpemo.admin.manage.account.index', ['type' => $type]);
    }

    //function to navigate to the upate page
    public function updatePage($type, $user_id){
        $validRoles = ['public_user', 'bpemo_staff', 'lgu_responder', 'barangay_official'];

        if (!in_array($type, $validRoles)) {
            abort(404, 'Invalid user role');
        }

        // Fetch the user by ID
        $user = User::find($user_id);

        return Inertia::render('manage-account/Update',[
            'user' => $user,
        ]);
    }

    //update logic
    public function update(Request $request, $type, $user_id)
    {
        $validRoles = ['public_user', 'bpemo_staff', 'lgu_responder', 'barangay_official'];

        if (!in_array($type, $validRoles)) {
            abort(404, 'Invalid user role');
        }
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user_id,
            'contact_number' => 'required|string|min:11',
            'birthdate' => 'required|date',
            'sex' => 'required|in:male,female,other',
            'position' => 'required|string|max:255',
            'municipality_id' => 'required|exists:municipalities,id',
            'barangay_id' => 'required|exists:barangays,id',
            'is_active' => 'required|boolean'
        ]);

        // If validation fails, return with error messages
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Find the user by ID
        $user = User::findOrFail($user_id);

        // Check and update only the fields that have changed
        if ($request->has('first_name') && $user->first_name !== $request->first_name) {
            $user->first_name = $request->first_name;
        }

        if ($request->has('last_name') && $user->last_name !== $request->last_name) {
            $user->last_name = $request->last_name;
        }

        if ($request->has('email') && $user->email !== $request->email) {
            $user->email = $request->email;
        }

        if ($request->has('contact_number') && $user->contact_number !== $request->contact_number) {
            $user->contact_number = $request->contact_number;
        }

        if ($request->has('birthdate') && $user->birthdate !== $request->birthdate) {
            $user->birthdate = $request->birthdate;
        }

        if ($request->has('sex') && $user->sex !== $request->sex) {
            $user->sex = $request->sex;
        }

        if ($request->has('position') && $user->position !== $request->position) {
            $user->position = $request->position;
        }

        if ($request->has('municipality_id') && $user->municipality_id !== $request->municipality_id) {
            $user->municipality_id = $request->municipality_id;
        }

        if ($request->has('barangay_id') && $user->barangay_id !== $request->barangay_id) {
            $user->barangay_id = $request->barangay_id;
        }

        if ($request->has('is_active') && $user->is_active !== $request->is_active) {
            $user->is_active = $request->is_active;
        }

        // Save the updated user data only if there are changes
        if ($user->isDirty()) {
            $user->save();
        }else{
            return redirect()->back()->withErrors('No changes')->withInput();

        }

        // Redirect back with success message
        return redirect()->route('bpemo.admin.manage.account.view', ['user_id' => $user->id])
                        ->with('success', 'User account updated successfully.');
    }

    public function disable(Request $request, $type, $user_id)
    {
        $validRoles = ['public_user', 'bpemo_staff', 'lgu_responder', 'barangay_official'];

        // Check if the user role is valid
        if (!in_array($type, $validRoles)) {
            abort(404, 'Invalid user role');
        }

        // Validate the request, ensuring the password is provided
        $request->validate([
            'password' => 'required|string',
        ]);

        // Check if the provided password matches the authenticated user's password
        $currentUser = Auth::user();
        if (!Hash::check($request->password, $currentUser->password)) {
            return back()->withErrors(['password' => 'The provided password is incorrect.']);
        }

        // Find the user by ID
        $user = User::findOrFail($user_id);

        // Ensure the authenticated user is not disabling their own account
        if ($user->id === $currentUser->id) {
            return back()->withErrors(['error' => 'You cannot disable your own account.']);
        }

        // Mark the user as inactive
        $user->is_active = false;

        // Save the changes to the user
        $user->save();

        // Redirect back with success message
        return redirect()->route('bpemo.admin.manage.account.view', ['user_id' => $user->id])
                        ->with('success', 'You have successfully disabled the account!');
    }

    public function activate(Request $request, $type, $user_id)
    {
        $validRoles = ['public_user', 'bpemo_staff', 'lgu_responder', 'barangay_official'];

        // Check if the user role is valid
        if (!in_array($type, $validRoles)) {
            abort(404, 'Invalid user role');
        }

        // Validate the request, ensuring the password is provided
        $request->validate([
            'password' => 'required|string',
        ]);

        // Check if the provided password matches the authenticated user's password
        $currentUser = Auth::user();
        if (!Hash::check($request->password, $currentUser->password)) {
            return back()->withErrors(['password' => 'The provided password is incorrect.']);
        }

        // Find the user by ID
        $user = User::findOrFail($user_id);

        // Ensure the authenticated user is not disabling their own account
        if ($user->id === $currentUser->id) {
            return back()->withErrors(['error' => 'You cannot disable your own account.']);
        }

        // Mark the user as inactive
        $user->is_active = true;

        // Save the changes to the user
        $user->save();

        // Redirect back with success message
        return redirect()->route('bpemo.admin.manage.account.view', ['user_id' => $user->id])
                        ->with('success', 'You have successfully activated the account!');
    }

}
