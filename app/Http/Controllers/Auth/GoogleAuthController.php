<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Sanctum\PersonalAccessToken;

class GoogleAuthController extends Controller
{
    /**
     * Step 1: Redirect user to Google OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->stateless() // For API / Angular flow
            ->redirect();
    }

    /**
     * Step 2: Handle Google callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            if (!$request->has('code')) {
                throw new \Exception('Missing authorization code from Google');
            }

            DB::beginTransaction();

            // Get Google user
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Check if user exists
            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if (!$user) {
                // Generate unique code
                do {
                    $newCode = max(
                        User::max('code') ?? 700,
                        Resource::max('code') ?? 700
                    ) + 1;
                } while (
                    User::where('code', $newCode)->exists() ||
                    Resource::where('code', $newCode)->exists()
                );

                // Create user
                $user = User::create([
                    'fname'       => $googleUser->getName(),
                    'lname'       => null,
                    'fullname'    => $googleUser->getName(),
                    'email'       => $googleUser->getEmail(),
                    'google_id'   => $googleUser->getId(),
                    'password'    => Hash::make('Myracepics123@'),
                    'code'        => $newCode,
                    'is_online'   => true,
                    'role'        => null,
                    'role_code'   => null,
                ]);

                // Create resource
                Resource::create([
                    'code'       => $newCode,
                    'fname'      => $googleUser->getName(),
                    'lname'      => null,
                    'fullname'   => $googleUser->getName(),
                    'email'      => $googleUser->getEmail(),
                    'role'       => null,
                    'role_code'  => null,
                    'coverphoto' => 'default.jpg',
                ]);
            } else {
                $user->update(['is_online' => true]);
            }

            DB::commit();

            // Generate API token
            $token = $user->createToken('google-token')->plainTextToken;
            $frontend = config('app.frontend.url', 'http://localhost:4200');

            // Role-based redirect
            if (!$user->role) {
                // No role → Angular role selection
                return redirect()->to("{$frontend}/auth/google/select-role?token={$token}");
            }

            // Role exists → role-specific Angular route
            $redirectUrl = match ($user->role) {
                'runner' => "{$frontend}/runner/allevents?token={$token}",
                'photographer' => "{$frontend}/photographer/allevents?token={$token}",
                default => "{$frontend}/auth/google/select-role?token={$token}"
            };

            return redirect()->to($redirectUrl);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Google OAuth Callback Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Google authentication failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Step 3: Set Google user role (from Angular)
     */
    public function setGoogleRole(Request $request)
    {
        // Validate role
        $request->validate([
            'role' => 'required|in:runner,photographer',
        ]);

        try {
            // Get token from query param
            $tokenValue = $request->query('token');
            if (!$tokenValue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing token.'
                ], 401);
            }

            // Get user from token
            $accessToken = PersonalAccessToken::findToken($tokenValue);
            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token.'
                ], 401);
            }

            $user = $accessToken->tokenable;

            // Prevent role overwrite
            if ($user->role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role already exists.',
                    'current_role' => $user->role
                ], 400);
            }

            $roleCodeMap = [
                'runner'       => 'DEF-USERS',
                'photographer' => 'DEF-PHOTOGRAPHER',
            ];

            // Atomic update
            DB::transaction(function () use ($user, $request, $roleCodeMap) {
                $user->update([
                    'role'      => $request->role,
                    'role_code' => $roleCodeMap[$request->role],
                ]);

                $resource = Resource::where('code', $user->code)->first();
                if ($resource) {
                    $resource->update([
                        'role'      => $request->role,
                        'role_code' => $roleCodeMap[$request->role],
                    ]);
                }
            });

            // Return redirect URL for Angular
            $frontend = config('app.frontend.url', 'http://localhost:4200');
            $redirectUrl = match ($request->role) {
                'runner' => "{$frontend}/runner/allevents?token={$tokenValue}",
                'photographer' => "{$frontend}/photographer/allevents?token={$tokenValue}",
            };

            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully.',
                'redirect_url' => $redirectUrl
            ]);

        } catch (\Throwable $e) {
            Log::error('Set Google role error: '.$e->getMessage(), [
                'token' => $request->query('token'),
                'role'  => $request->role,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to set role.'
            ], 500);
        }
    }
}
