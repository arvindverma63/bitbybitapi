<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\UserProfile;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class FacebookController extends Controller
{
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->stateless()->redirect();
    }

    public function handleFacebookCallback()
    {
        try {
            $facebookUser = Socialite::driver('facebook')->stateless()->user();

            // Find or create the user
            $user = User::firstOrCreate(
                ['email' => $facebookUser->email],
                [
                    'name' => $facebookUser->name,
                    'facebook_id' => $facebookUser->id,
                    'password' => bcrypt(uniqid()), // Random password for security
                ]
            );

            // Update or create the user profile
            UserProfile::updateOrCreate(
                ['userId' => $user->id], // Using 'userId' as the foreign key
                [
                    'firstName' => $facebookUser->name,
                    'avatar' => $facebookUser->getAvatar(),
                ]
            );

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            // Redirect to frontend with token and user details
            $frontendCallback = sprintf(
                'https://testxyz-eight.vercel.app/callback?access_token=%s&name=%s&email=%s',
                urlencode($token),
                urlencode($user->name),
                urlencode($user->email)
            );

            return redirect($frontendCallback);
        } catch (Exception $e) {
            // Redirect to frontend with error
            return redirect('https://testxyz-eight.vercel.app/login?error=' . urlencode($e->getMessage()));
        }
    }
}
