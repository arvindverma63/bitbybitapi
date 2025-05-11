<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema(
 *     schema="Post",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="userId", type="integer", example=1),
 *     @OA\Property(property="post", type="string", example="Post content"),
 *     @OA\Property(property="tags", type="string", example="tag1,tag2", nullable=true),
 *     @OA\Property(property="category", type="string", example="Technology"),
 *     @OA\Property(property="title", type="string", example="Post Title"),
 *     @OA\Property(property="notification", type="integer", example=0),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class PostController extends Controller
{
    /**
     * @OA\Get(
     *     path="/posts",
     *     summary="Get all posts",
     *     tags={"Posts"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Post")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $posts = Post::all();
        return response()->json($posts);
    }

    /**
     * @OA\Post(
     *     path="/posts",
     *     summary="Create a new post",
     *     tags={"Posts"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"userId","post","category","title"},
     *             @OA\Property(property="userId", type="integer", example=1),
     *             @OA\Property(property="post", type="string", example="Post content"),
     *             @OA\Property(property="tags", type="string", example="tag1,tag2", nullable=true),
     *             @OA\Property(property="category", type="string", example="Technology"),
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
        $validator = Validator::make($request->all(), [
            'userId' => 'required|integer',
            'post' => 'required|string',
            'tags' => 'nullable|string',
            'category' => 'required|string',
            'title' => 'required|string',
            'notification' => 'integer|in:0,1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $post = Post::create($request->all());
        return response()->json($post, 201);
    }

    /**
     * @OA\Get(
     *     path="/posts/{id}",
     *     summary="Get a specific post",
     *     tags={"Posts"},
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
        $post = Post::findOrFail($id);
        return response()->json($post);
    }

    /**
     * @OA\Put(
     *     path="/posts/{id}",
     *     summary="Update a post",
     *     tags={"Posts"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"userId","post","category","title"},
     *             @OA\Property(property="userId", type="integer", example=1),
     *             @OA\Property(property="post", type="string", example="Updated content"),
     *             @OA\Property(property="tags", type="string", example="tag1,tag2", nullable=true),
     *             @OA\Property(property="category", type="string", example="Technology"),
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
        $post = Post::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'userId' => 'required|integer',
            'post' => 'required|string',
            'tags' => 'nullable|string',
            'category' => 'required|string',
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
     *     path="/posts/{id}",
     *     summary="Delete a post",
     *     tags={"Posts"},
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
        $post = Post::findOrFail($id);
        $post->delete();
        return response()->json(null, 204);
    }
}
