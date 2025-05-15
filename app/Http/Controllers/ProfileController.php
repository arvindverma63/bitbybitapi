<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * @OA\Post(
     *     path="/update-profile",
     *     summary="Update user profile",
     *     description="Updates the user profile including nullable fields and image uploads",
     *     operationId="updateProfile",
     *     tags={"Profile"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User profile data and optional file uploads",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="firstName", type="string", example="John"),
     *                 @OA\Property(property="lastName", type="string", example="Doe"),
     *                 @OA\Property(property="about", type="string", example="I am a software developer."),
     *                 @OA\Property(property="lastseen", type="string", example="2025-05-08 12:34:56"),
     *                 @OA\Property(
     *                     property="avatar",
     *                     type="string",
     *                     format="binary",
     *                     description="Profile avatar image (jpg, jpeg, png, gif, max 2MB)"
     *                 ),
     *                 @OA\Property(
     *                     property="banners",
     *                     type="string",
     *                     format="binary",
     *                     description="Profile banner image (jpg, jpeg, png, gif, max 2MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Profile updated successfully."),
     *             @OA\Property(property="profile", type="object",
     *                 @OA\Property(property="userId", type="integer"),
     *                 @OA\Property(property="firstName", type="string"),
     *                 @OA\Property(property="lastName", type="string"),
     *                 @OA\Property(property="about", type="string"),
     *                 @OA\Property(property="lastseen", type="string"),
     *                 @OA\Property(property="avatar", type="stocking", example="https://i.ibb.co/w04Prt6/c1f64245afb2.gif"),
     *                 @OA\Property(property="banners", type="string", example="https://i.ibb.co/98W13PY/c1f64245afb2.gif"),
     *                 @OA\Property(property="created_at", type="string"),
     *                 @OA\Property(property="updated_at", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while updating the profile.")
     *         )
     *     )
     * )
     */
    public function updateProfile(Request $request)
    {
        try {
            Log::info('Starting profile update for user', ['userId' => Auth::id()]);

            $validatedData = $request->validate([
                'firstName' => 'nullable|string|max:255',
                'lastName' => 'nullable|string|max:255',
                'about' => 'nullable|string',
                'lastseen' => 'nullable|string',
                'avatar' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:2048',
                'banners' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:2048',
            ]);

            $userId = Auth::id();
            $profile = UserProfile::firstOrNew(['userId' => $userId]);

            if ($request->hasFile('avatar')) {
                Log::info('Uploading avatar for user', ['userId' => $userId]);
                $avatarUrl = $this->uploadImageToImgBB($request->file('avatar'));
                if ($avatarUrl) {
                    $profile->avatar = $avatarUrl;
                    Log::info('Avatar uploaded successfully', ['url' => $avatarUrl]);
                } else {
                    Log::warning('Avatar upload failed for user', ['userId' => $userId]);
                }
            }

            if ($request->hasFile('banners')) {
                Log::info('Uploading banner for user', ['userId' => $userId]);
                $bannerUrl = $this->uploadImageToImgBB($request->file('banners'));
                if ($bannerUrl) {
                    $profile->banners = $bannerUrl;
                    Log::info('Banner uploaded successfully', ['url' => $bannerUrl]);
                } else {
                    Log::warning('Banner upload failed for user', ['userId' => $userId]);
                }
            }

            $profile->firstName = $validatedData['firstName'] ?? $profile->firstName;
            $profile->lastName = $validatedData['lastName'] ?? $profile->lastName;
            $profile->about = $validatedData['about'] ?? $profile->about;
            $profile->lastseen = $validatedData['lastseen'] ?? $profile->lastseen;
            $profile->save();

            Log::info('Profile updated successfully for user', ['userId' => $userId]);

            return response()->json([
                'message' => 'Profile updated successfully.',
                'profile' => $profile,
            ]);
        } catch (\Exception $e) {
            Log::error('Profile update failed', [
                'userId' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An error occurred while updating the profile.'
            ], 500);
        }
    }

    private function uploadImageToImgBB($file)
    {
        try {
            Log::info('Attempting to upload image to ImgBB', ['filename' => $file->getClientOriginalName()]);

            $apiKey = env('IMGBB_API_KEY');
            $response = Http::attach(
                'image',
                file_get_contents($file),
                $file->getClientOriginalName()
            )->post("https://api.imgbb.com/1/upload?key=$apiKey");

            if ($response->successful()) {
                $url = $response->json()['data']['url'];
                Log::info('Image uploaded to ImgBB successfully', ['url' => $url]);
                return $url;
            }

            Log::warning('Image upload to ImgBB failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Error uploading image to ImgBB', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * @OA\Delete(
     *     path="/profile",
     *     summary="Delete user profile",
     *     description="Delete the authenticated user's profile and associated images",
     *     operationId="deleteProfile",
     *     tags={"Profile"},
     *     @OA\Response(
     *         response=200,
     *         description="Profile deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Profile deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Profile not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Profile not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the profile.")
     *         )
     *     )
     * )
     */
    public function deleteProfile()
    {
        try {
            $userId = Auth::id();
            $profile = UserProfile::where('userId', $userId)->first();

            if (!$profile) {
                Log::info('Profile not found for deletion', ['userId' => $userId]);
                return response()->json([
                    'message' => 'Profile not found.'
                ], 404);
            }

            if ($profile->avatar) {
                $this->deleteImageFromImgBB($profile->avatar);
            }
            if ($profile->banners) {
                $this->deleteImageFromImgBB($profile->banners);
            }

            $profile->delete();
            Log::info('Profile deleted successfully', ['userId' => $userId]);

            return response()->json([
                'message' => 'Profile deleted successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting profile', [
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'An error occurred while deleting the profile.'
            ], 500);
        }
    }
    /**
     * Get the authenticated user's profile
     *
     * @OA\Get(
     *     path="/user-profile",
     *     summary="Get user profile",
     *     description="Retrieve the authenticated user's profile data",
     *     operationId="getProfile",
     *     tags={"Profile"},
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="profile", type="object",
     *                 @OA\Property(property="userId", type="integer", example=1),
     *                 @OA\Property(property="firstName", type="string", example="John", nullable=true),
     *                 @OA\Property(property="lastName", type="string", example="Doe", nullable=true),
     *                 @OA\Property(property="about", type="string", example="I am a software developer.", nullable=true),
     *                 @OA\Property(property="lastseen", type="string", example="2025-05-08 12:34:56", nullable=true),
     *                 @OA\Property(property="avatar", type="string", example="https://i.ibb.co/w04Prt6/c1f64245afb2.gif", nullable=true),
     *                 @OA\Property(property="banners", type="string", example="https://i.ibb.co/98W13PY/c1f64245afb2.gif", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Profile not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Profile not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving the profile.")
     *         )
     *     )
     * )
     */
    public function getProfile()
    {
        try {
            $userId = Auth::id();
            $profile = UserProfile::where('userId', $userId)->first();

            if (!$profile) {
                Log::info('Profile not found for user', ['userId' => $userId]);
                return response()->json([
                    'message' => 'Profile not found.'
                ], 404);
            }

            Log::info('Profile retrieved successfully', ['userId' => $userId]);
            return response()->json([
                'profile' => $profile
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving profile', [
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'An error occurred while retrieving the profile.'
            ], 500);
        }
    }
}
