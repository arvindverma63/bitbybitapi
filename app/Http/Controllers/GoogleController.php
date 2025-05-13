<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;


class GoogleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/login/google",
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
     *     path="/auth/google/callback",
     *     tags={"Authentication"},
     *     summary="Handle Google OAuth callback and return JWT token",
     *     description="Processes the Google OAuth callback, authenticates the user, generates a JWT token, and returns it for frontend use.",
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
     *         response=200,
     *         description="Successful authentication with JWT token",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com")
     *             ),
     *             @OA\Property(property="redirect_url", type="string", example="http://your-webapp.com/dashboard")
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
            } else {
                // Update google_id if user exists
                $user->update(['google_id' => $googleUser->id]);
            }

            // Log the user in
            Auth::login($user);

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            // Define the redirect URL for your web app
            $redirectUrl = 'http://your-webapp.com/dashboard';

            // Return JSON response with token and redirect URL
            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'redirect_url' => $redirectUrl,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }
}
