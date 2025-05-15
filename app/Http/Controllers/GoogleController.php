<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;


class GoogleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/login/google",
     *     tags={"Authentication"},
     *     summary="Redirect to Google for OAuth authentication",
     *     description="Redirects the user to Google's OAuth consent screen to initiate authentication.",
     *     operationId="redirectToGoogle",
     *     @OA\Response(
     *         response=302,
     *         description="Redirects to Google OAuth page",
     *         @OA\Header(
     *             header="Location",
     *             description="URL to Google's OAuth consent screen",
     *             @OA\Schema(type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * @OA\Get(
     *     path="/api/auth/google/callback",
     *     tags={"Authentication"},
     *     summary="Handle Google OAuth callback and redirect to frontend",
     *     description="Processes the Google OAuth callback, generates a JWT token, and redirects to the frontend with the token.",
     *     operationId="handleGoogleCallback",
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="Authorization code returned by Google",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="state",
     *         in="query",
     *         description="State parameter for CSRF protection",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirects to frontend with token and user details",
     *         @OA\Header(
     *             header="Location",
     *             description="Frontend callback URL with token",
     *             @OA\Schema(type="string", example="https://testxyz-eight.vercel.app/callback?access_token=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...&name=John%20Doe&email=john.doe@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request due to invalid code or state",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid authorization code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Something went wrong")
     *         )
     *     )
     * )
     */
    public function handleGoogleCallback()
    {
        try {
            // Use stateless for API
            $googleUser = Socialite::driver('google')->stateless()->user();
            $user = User::where('email', $googleUser->email)->first();

            if (!$user) {
                // Create a new user if not found
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => bcrypt(uniqid()), // Random password
                ]);
                if ($user) {
                    $profile = UserProfile::create([
                        'userId' => $user->id,
                        'firstName' => $googleUser->name,
                        'image' => $googleUser->getAvatar(),
                    ]);
                }
            } else {
                // Update google_id if user exists
                $user->update(['google_id' => $googleUser->id]);
                $profileUpdate = UserProfile::update([
                    'firstName'=>$googleUser->name,
                    'userId'=>$user->id,
                    'image'=>$googleUser->getAvatar()
                ]);
            }

            // Log the user in
            Auth::login($user);

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            // Define the frontend callback URL with token and user details
            $frontendCallback = sprintf(
                'https://testxyz-eight.vercel.app/callback?access_token=%s&name=%s&email=%s',
                urlencode($token),
                urlencode($user->name),
                urlencode($user->email)
            );

            // Redirect to frontend
            return redirect($frontendCallback);
        } catch (Exception $e) {
            // Redirect to frontend with error
            return redirect('https://testxyz-eight.vercel.app/login?error=' . urlencode('Authentication failed: ' . $e->getMessage()));
        }
    }
}
