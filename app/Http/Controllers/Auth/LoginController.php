<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; 
use App\Models\Userprofile;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\DB;



class LoginController extends Controller
{
    public function login(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ]);
        }

        // Attempt login
        if (!Auth::attempt([
            'email' => $request->email,
            'password' => $request->password
        ])) {
            return response()->json([
                'success' => false,
                'message' => 'The email or password is incorrect. Please check your credentials.'
            ]);
        }

        $user = Auth::user();

        // Remove old tokens
        $user->tokens()->delete();

        // Update online status
        $user->is_online = true;
        $user->save();

        // Create token
        $token = $user->createToken('Personal Access Token')->plainTextToken;

        // Role mapping
        $roleMap = [
            'DEF-USERS'        => 'runner',
            'DEF-ADMIN'       => 'admin',
            'DEF-MASTERADMIN' => 'masteradmin',
            'DEF-PHOTOGRAPHER'      => 'photographer',
        ];

        $roleName = $roleMap[$user->role_code] ?? 'unknown';

        // Check profile existence for DEF-USER
        $userProfileExists = Userprofile::where('code', $user->code)->exists();

        // Message flag logic
        $messageFlag = (
            $user->role_code === 'DEF-PHOTOGRAPHER' ||
            in_array($user->role_code, ['DEF-ADMIN', 'DEF-MASTERADMIN']) ||
            ($user->role_code === 'DEF-USERS' && $userProfileExists)
        ) ? 0 : 1;

        return response()->json([
            'success'   => true,
            'token'     => $token,
            'role'      => $roleName,
            'role_code' => $user->role_code,
            'message'   => $messageFlag,
            'is_online' => true
        ]);
    }
            
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->tokens()->delete(); // revoke all tokens
            $user->is_online = false;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function getIsOnline()
    {
        $users = DB::table('users')->select('code', 'is_online')->get();
        return response()->json([
            'success' => true,
            'online' => $users->where('is_online', true)->values(),
            'offline' => $users->where('is_online', false)->values()
        ]);
    }


}


// login POST
// {
//     "email" : "TEST@gmail.com", 
//     "password": "1",
// }