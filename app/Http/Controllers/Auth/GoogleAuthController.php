<?php

namespace App\Http\Controllers\Auth;
use Google\Auth\AccessToken;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

use Illuminate\Support\Facades\Auth;

class GoogleAuthController extends Controller
{
    
    public function googleLogin(Request $request)
    {
        $request->validate([
            'credential' => 'required|string'
        ]);

        $token = $request->credential;

        $auth = new AccessToken();
        $payload = $auth->verify($token);

        if (!$payload) {
            return response()->json(['message' => 'Invalid Google token'], 401);
        }

        $user = User::updateOrCreate(
            ['email' => $payload['email']],
            [
                'name'       => $payload['name'],
                'google_id'  => $payload['sub'],
                'avatar'     => $payload['picture'] ?? null,
                'password'   => bcrypt(Str::random(16)),
            ]
        );

        $apiToken = $user->createToken('google-login')->plainTextToken;

        return response()->json([
            'token' => $apiToken,
            'user'  => $user
        ]);
    }

    public function redirect()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            // SIGN UP
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user);

        $token = $user->createToken('google-auth')->plainTextToken;

        // 🔁 REDIRECT TO ANGULAR
        return redirect()->away(
            env('FRONTEND_URL') . '/auth/google/callback?token=' . $token
        );
    }


    public function callbackxx()
    {
        $googleUser = Socialite::driver('google')
            ->stateless()
            ->user();

        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            // SIGN UP
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user);

        $token = $user->createToken('google-auth')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }


      // Step 1: Redirect to Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    // Step 2: Handle callback
       public function handleGoogleCallback(Request $request)
    {
        try {
            // Get user info from Google
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Find existing user
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Create user if not exists
                $user = User::create([
                    'name'      => $googleUser->getName(),
                    'email'     => $googleUser->getEmail(),
                    'password'  => bcrypt(str()->random(16)),
                    'role_code' => 'DEF-USERS',
                    'is_online' => true,
                    'code'      => str()->random(8),
                ]);

                Userprofile::create([
                    'code' => $user->code,
                    // default profile fields
                ]);
            }

            // Delete old tokens
            $user->tokens()->delete();

            // Update online status
            $user->is_online = true;
            $user->save();

            // Create new token
            $token = $user->createToken('Personal Access Token')->plainTextToken;

            // Role mapping
            $roleMap = [
                'DEF-USERS'        => 'runner',
                'DEF-ADMIN'        => 'admin',
                'DEF-MASTERADMIN'  => 'masteradmin',
                'DEF-PHOTOGRAPHER' => 'photographer',
            ];

            $roleName = $roleMap[$user->role_code] ?? 'unknown';

            // Profile existence
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Google login failed: ' . $e->getMessage()
            ]);
        }
    }


    
}
