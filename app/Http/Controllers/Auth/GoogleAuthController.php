<?php

namespace App\Http\Controllers\Auth;


use Google\Auth\AccessToken;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Userprofile;
use Illuminate\Support\Facades\Auth;

class GoogleAuthController extends Controller
{
    
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
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();

            // Find existing user
            $user = User::where('email', $googleUser->getEmail())->first();

            // Create user if not exists
            if (!$user) {
                $user = User::create([
                    'name'      => $googleUser->getName(),
                    'email'     => $googleUser->getEmail(),
                    'password'  => bcrypt(Str::random(16)),
                    'role_code' => 'DEF-USERS',
                    'is_online' => true,
                    'code'      => Str::upper(Str::random(8)),
                ]);

                Userprofile::create([
                    'code' => $user->code,
                ]);
            }

            // Delete old tokens (Sanctum)
            $user->tokens()->delete();

            // Update online status
            $user->update([
                'is_online' => true,
            ]);

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

            // Check profile
            $userProfileExists = Userprofile::where('code', $user->code)->exists();

            // Message flag logic
            $messageFlag = (
                $user->role_code === 'DEF-PHOTOGRAPHER' ||
                in_array($user->role_code, ['DEF-ADMIN', 'DEF-MASTERADMIN']) ||
                ($user->role_code === 'DEF-USERS' && $userProfileExists)
            ) ? 0 : 1;

            // Redirect back to Angular with token
            return redirect()->away(
                config('app.frontend_url') .
                '/auth/google-success?' .
                http_build_query([
                    'token'     => $token,
                    'role'      => $roleName,
                    'role_code' => $user->role_code,
                    'message'   => $messageFlag,
                ])
            );

        } catch (\Exception $e) {
            return redirect()->away(
                config('app.frontend_url') .
                '/auth/google-error?error=' .
                urlencode($e->getMessage())
            );
        }
    }


    
}
