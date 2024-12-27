<?php

namespace App\Http\Controllers\BpemoAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ManageAccountsController extends Controller
{
    //
    public function index($type){

        // Validate if the type is a valid user role
        $validRoles = ['bpemo_admin', 'bpemo_staff', 'lgu_responder', 'barangay_official', 'public_user'];

        if (!in_array($type, $validRoles)) {
            abort(404, 'Invalid user role');
        }

        $users = User::where('user_role', $type)->get();

        return Inertia::render('bpemo-admin/manage-account/index', [
            'users' => $users,
            'type' => $type
        ]);
    }


    public function create ($request){

    }
}
