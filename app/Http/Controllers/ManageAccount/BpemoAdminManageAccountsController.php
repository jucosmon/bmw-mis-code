<?php

namespace App\Http\Controllers\ManageAccount;

use App\Models\Municipality;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Barangay;
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
            'type' => $type,
            'success' => session('success')
        ]);
    }

    public function view($user_id)
    {
        $user = User::find($user_id);
        $municipalities = Municipality::all();
        $barangays = Barangay::all();

        return Inertia::render('manage-account/View', [
            'user' => $user,
            'municipalities' => $municipalities,
            'barangays' => $barangays,
            'success' => session('success')
        ]);
    }

    public function createPage($type){
        $validRoles = ['bpemo_admin', 'bpemo_staff', 'lgu_responder', 'barangay_official'];

        if (!in_array($type, $validRoles)) {
            abort(404, 'Invalid user role');
        }

        $municipalities = Municipality::all();
        $barangays = Barangay::all();

        return Inertia::render('manage-account/Create',[
            'type' => $type,
            'municipalities' => $municipalities,
            'barangays' => $barangays
        ]);
    }

    public function create(Request $request, $type): RedirectResponse
    {
        // Validate all required fields
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'contact_number' => 'nullable|string|min:10|max:10',
            'birthdate' => 'required|date',
            'sex' => 'required|in:male,female,other',
            'position' => 'required|string|max:100',
            'municipality_id' => 'required|integer|exists:municipalities,id',
            'barangay_id' => 'required|integer|exists:barangays,id',
        ]);

        $defaultPassword = Str::random(12);
        // Create the user with all fields
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($defaultPassword),
            'contact_number' =>  $request->contact_number,
            'birthdate' => $request->birthdate,
            'sex' => $request->sex,
            'user_role' => $type,
            'position' => $request->position,
            'municipality_id' => $request->municipality_id,
            'barangay_id' => $request->barangay_id,
        ]);

        $user->notify(new CustomVerifyEmail($defaultPassword));

        // Redirect to the manage account index with the user type
        return redirect()->route('bpemo.admin.manage.account.index', ['type' => $type])
        ->with('success', 'You have successfully created an account!.');
    }

    //function to navigate to the upate page
    public function updatePage($type, $user_id){
        $validRoles = ['public_user', 'bpemo_staff', 'lgu_responder', 'barangay_official'];

        if (!in_array($type, $validRoles)) {
            abort(404, 'Invalid user role');
        }

        $user = User::find($user_id);
        $municipalities = Municipality::all();
        $barangays = Barangay::all();

        return Inertia::render('manage-account/Update',[
            'user' => $user,
            'municipalities' => $municipalities,
            'barangays' => $barangays
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
            'contact_number' => 'nullable|string|min:10|max:10',
            'birthdate' => 'required|date',
            'sex' => 'required|in:male,female,other',
            'position' => 'nullable|string|max:255',
            'municipality_id' => 'nullable|exists:municipalities,id',
            'barangay_id' => 'nullable|exists:barangays,id',
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

        // Save the updated user data only if there are changes
        if ($user->isDirty()) {
            $user->save();
        }else{
            return redirect()->back()->withErrors('No changes')->withInput();

        }

        // Redirect back with success message
        return redirect()->route('bpemo.admin.manage.account.view', ['user_id' => $user->id])
                        ->with('success', 'You have successfully updated an account!.');
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
                        ->with('success', 'You have successfully disabled an account!');
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
                        ->with('success', 'You have successfully activated an account!');
    }

}
