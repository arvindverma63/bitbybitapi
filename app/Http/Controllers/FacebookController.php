<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class FacebookController extends Controller
{
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function handleFacebookCallback()
    {
        try {
            $facebookUser = Socialite::driver('facebook')->user();

            // Find or create the user
            $user = User::where('email', $facebookUser->email)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $facebookUser->name,
                    'email' => $facebookUser->email,
                    'facebook_id' => $facebookUser->id,
                    'password' => bcrypt('dummy_password'), // Not needed for social login
                ]);
            } else {
                $user->update(['facebook_id' => $facebookUser->id]);
            }

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
            return redirect('https://testxyz-eight.vercel.app/login?error=' . urlencode($e->getMessage()));
        }
    }
}
