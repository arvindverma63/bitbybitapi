<?php

namespace App\Http\Controllers;

use App\Models\PostImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;

class PostImagesController extends Controller
{
    /**
     * @OA\Post(
     *     path="/images",
     *     tags={"Images"},
     *     summary="Upload a new image",
     *     description="Uploads an image file and associates it with the authenticated user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="image",
     *                     description="Image file to upload",
     *                     type="string",
     *                     format="binary"
     *                 ),
     *                 required={"image"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Image uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Image uploaded successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string", example="https://i.ibb.co/example.jpg"),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid image file",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid image file")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Validation failed"),
     *             @OA\Property(
     *                 property="messages",
     *                 type="object",
     *                 @OA\Property(
     *                     property="image",
     *                     type="array",
     *                     @OA\Items(type="string", example="The image must be a file of type: png, jpg, webp, gif.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="An error occurred while uploading the image")
     *         )
     *     )
     * )
     */
    public function saveImage(Request $request)
    {
        try {
            Log::info('Starting Upload Image for PostImage', ['userId' => Auth::id()]);
            // Validate the request
            $validated = $request->validate([
                'image' => 'required|file|mimes:png,jpg,webp,gif|max:2048' // Max size 2MB
            ]);

            $userId = Auth::id();

            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                // Upload image to ImgBB
                $imageUrl = $this->uploadImageToImgBB($request->file('image'));

                if (!$imageUrl) {
                    return response()->json(['error' => 'Image upload failed'], 500);
                }

                // Create post image record
                $postImage = PostImages::create([
                    'image' => $imageUrl,
                    'userId' => $userId,
                ]);

                return response()->json([
                    'message' => 'Image uploaded successfully',
                    'data' => $postImage
                ], 201);
            }

            return response()->json(['error' => 'Invalid image file'], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Image upload failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'An error occurred while uploading the image'
            ], 500);
        }
    }

    private function uploadImageToImgBB(UploadedFile $file): ?string
    {
        try {
            Log::info('Attempting to upload image to ImgBB', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType()
            ]);

            $apiKey = env('IMGBB_API_KEY');
            if (!$apiKey) {
                Log::error('ImgBB API key not configured');
                return null;
            }

            $response = Http::timeout(30) // Set 30-second timeout
                ->attach(
                    'image',
                    fopen($file->getPathname(), 'r'),
                    $file->getClientOriginalName()
                )
                ->post("https://api.imgbb.com/1/upload?key={$apiKey}");

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['url'])) {
                    $url = $data['data']['url'];
                    Log::info('Image uploaded to ImgBB successfully', ['url' => $url]);
                    return $url;
                }
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
}
