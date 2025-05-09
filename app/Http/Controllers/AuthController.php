<?php

// app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\URL;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="BitByBit API",
 *     description="API documentation for BitByBit",
 *     @OA\Contact(
 *         name="BitByBit Team",
 *         email="support@bitbybit.com",
 *         url="https://bitbybit.com"
 *     )
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="api_key",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->only(['profile', 'logout', 'resendVerification']);
    }

    /**
     * @OA\Post(
     *     path="/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User registered successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if($user){
            UserProfile::create([
                'firstName'=>$request->name,
                'userId'=> $user->id,
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'User registered successfully. Please verify your email.'], 200);
    }

    /**
     * @OA\Post(
     *     path="/login",
     *     summary="User login",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Token generated successfully"),
     *     @OA\Response(response=401, description="Invalid credentials or unverified email")
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->hasVerifiedEmail()) {
            return response()->json(['error' => 'Email not verified'], 401);
        }

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        return response()->json(['token' => $token]);
    }

    /**
     * @OA\Get(
     *     path="/profile",
     *     tags={"Authentication"},
     *     summary="Get user profile",
     *     security={{"api_key":{}}},
     *     @OA\Response(response=200, description="User profile returned successfully"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function profile()
    {
        return response()->json(auth()->user());
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     tags={"Authentication"},
     *     summary="User logout",
     *     security={{"api_key":{}}},
     *     @OA\Response(response=200, description="Successfully logged out"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * @OA\Post(
     *     path="/password/email",
     *     tags={"Authentication"},
     *     summary="Send password reset link",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="john@example.com")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset link sent"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function sendPasswordResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)], 200)
            : response()->json(['error' => __($status)], 422);
    }

    /**
     * @OA\Post(
     *     path="/password/reset",
     *     tags={"Authentication"},
     *     summary="Reset password",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password","password_confirmation","token"},
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", example="newpassword123"),
     *             @OA\Property(property="token", type="string", example="reset-token")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)], 200)
            : response()->json(['error' => __($status)], 422);
    }

    /**
     * @OA\Get(
     *     path="/email/verify/{id}/{hash}",
     *     tags={"Authentication"},
     *     summary="Verify email address",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="hash",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Email verified successfully"),
     *     @OA\Response(response=400, description="Invalid verification link")
     * )
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['error' => 'Invalid verification link'], 400);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return response()->json(['message' => 'Email verified successfully']);
    }

    /**
     * @OA\Post(
     *     path="/email/verification-notification",
     *     tags={"Authentication"},
     *     summary="Resend email verification",
     *     security={{"api_key":{}}},
     *     @OA\Response(response=200, description="Verification link sent"),
     *     @OA\Response(response=400, description="Email already verified"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function resendVerification(Request $request)
    {
        $user = auth()->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['error' => 'Email already verified'], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent']);
    }

    /**
     * @OA\Post(
     *     path="/check-username",
     *     summary="Check if a username is available",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username"},
     *             @OA\Property(property="username", type="string", example="johndoe")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Username is available"),
     *     @OA\Response(response=409, description="Username already exists")
     * )
     */

    public function checkUserName(Request $request)
    {
        $user = User::where('name', $request->username)->first();

        if ($user) {
            return response()->json(['error' => 'Username already exists'], 409);
        }

        return response()->json(['success' => 'Username is available'], 200);
    }
}
