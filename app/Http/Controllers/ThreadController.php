<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema(
 *     schema="Post",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="userId", type="integer", example=1),
 *     @OA\Property(property="post", type="string", example="Post content"),
 *     @OA\Property(property="tags", type="string", example="tag1,tag2", nullable=true),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Post Title"),
 *     @OA\Property(property="notification", type="integer", example=0),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ThreadController extends Controller
{
    /**
     * @OA\Get(
     *     path="/threads",
     *     summary="Get paginated posts",
     *     tags={"Thread"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of posts per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Post")
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="total", type="integer", example=50),
     *             @OA\Property(property="per_page", type="integer", example=10)
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $perPage = request()->query('per_page', 10);
        $postsPaginated = Thread::paginate($perPage);

        return response()->json([
            'data' => $postsPaginated->items(),
            'current_page' => $postsPaginated->currentPage(),
            'last_page' => $postsPaginated->lastPage(),
            'total' => $postsPaginated->total(),
            'per_page' => $postsPaginated->perPage(),
        ]);
    }


    /**
     * @OA\Post(
     *     path="/threads",
     *     summary="Create a new post",
     *     tags={"Threads"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="post", type="string", example="Post content"),
     *             @OA\Property(property="tags", type="string", example="tag1,tag2", nullable=true),
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Post Title"),
     *             @OA\Property(property="notification", type="integer", example=0)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Post created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Post")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'post' => 'required|string',
            'tags' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string',
            'notification' => 'integer|in:0,1'
        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Add the authenticated user ID to the validated data
        $data = $validator->validated();
        $data['userId'] = Auth::id();

        // Create the post
        $post = Thread::create($data);

        // Return the created post
        return response()->json($post, 200);
    }


    /**
     * @OA\Get(
     *     path="/threads/{id}",
     *     summary="Get a specific post",
     *     tags={"Thread"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Post")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found"
     *     )
     * )
     */
    public function show($id): JsonResponse
    {
        $post = Thread::findOrFail($id);
        return response()->json($post);
    }

    /**
     * @OA\Put(
     *     path="/threads/{id}",
     *     summary="Update a post",
     *     tags={"Thread"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"userId","post","category_id","title"},
     *             @OA\Property(property="userId", type="integer", example=1),
     *             @OA\Property(property="post", type="string", example="Updated content"),
     *             @OA\Property(property="tags", type="string", example="tag1,tag2", nullable=true),
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Updated Title"),
     *             @OA\Property(property="notification", type="integer", example=0)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Post")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $post = Thread::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'userId' => 'required|integer',
            'post' => 'required|string',
            'tags' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string',
            'notification' => 'integer|in:0,1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $post->update($request->all());
        return response()->json($post);
    }

    /**
     * @OA\Delete(
     *     path="/threads/{id}",
     *     summary="Delete a post",
     *     tags={"Thread"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Post deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found"
     *     )
     * )
     */
    public function destroy($id): JsonResponse
    {
        $post = Thread::findOrFail($id);
        $post->delete();
        return response()->json(null, 204);
    }
}
