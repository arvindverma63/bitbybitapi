<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Posts",
 *     description="API endpoints for managing forum posts"
 * )
 */
class PostController extends Controller
{
    /**
     * @OA\Get(
     *     path="/threads/{thread_id}/posts",
     *     tags={"Posts"},
     *     summary="Get paginated list of posts in a thread",
     *     description="Returns a paginated list of posts for a given thread, including user and reaction data.",
     *     operationId="getPostsByThread",
     *     @OA\Parameter(
     *         name="thread_id",
     *         in="path",
     *         description="ID of the thread",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of posts per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="post_id", type="integer", example=1),
     *                     @OA\Property(property="thread_id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="content", type="string", example="This is a post."),
     *                     @OA\Property(property="is_edited", type="boolean", example=false),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-17T22:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-17T22:00:00Z"),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="John Doe")
     *                     ),
     *                     @OA\Property(
     *                         property="reactions",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="reaction_id", type="integer", example=1),
     *                             @OA\Property(property="post_id", type="integer", example=1),
     *                             @OA\Property(property="user_id", type="integer", example=1),
     *                             @OA\Property(property="reaction_type", type="string", enum={"LIKE", "DISLIKE"}, example="LIKE")
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="total", type="integer", example=50),
     *             @OA\Property(property="last_page", type="integer", example=4)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Thread not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Thread not found")
     *         )
     *     ),
     *
     * )
     */
    public function index($thread_id)
    {
        $posts = Post::where('thread_id', $thread_id)
            ->with(['user', 'reactions'])
            ->orderBy('created_at', 'asc')
            ->paginate(15); // Paginate with 15 posts per page
        return response()->json($posts);
    }

    /**
     * @OA\Post(
     *     path="/threads/{thread_id}/posts",
     *     tags={"Posts"},
     *     summary="Create a new post in a thread",
     *     description="Creates a new post for the authenticated user in the specified thread.",
     *     operationId="createPost",
     *     @OA\Parameter(
     *         name="thread_id",
     *         in="path",
     *         description="ID of the thread",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="content", type="string", example="This is a new post.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Post created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="post_id", type="integer", example=1),
     *             @OA\Property(property="thread_id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="content", type="string", example="This is a new post."),
     *             @OA\Property(property="is_edited", type="boolean", example=false),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-17T22:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-17T22:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Thread not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Thread not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The content field is required.")
     *         )
     *     ),
     *
     * )
     */
    public function store(Request $request, $thread_id)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $thread = Thread::findOrFail($thread_id);

        $post = Post::create([
            'thread_id' => $thread->id,
            'user_id' => Auth::id(),
            'content' => $request->content,
        ]);

        return response()->json($post, 201);
    }

    /**
     * @OA\Put(
     *     path="/posts/{post_id}",
     *     tags={"Posts"},
     *     summary="Update an existing post",
     *     description="Updates the content of a post owned by the authenticated user.",
     *     operationId="updatePost",
     *     @OA\Parameter(
     *         name="post_id",
     *         in="path",
     *         description="ID of the post",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="content", type="string", example="Updated post content.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="post_id", type="integer", example=1),
     *             @OA\Property(property="thread_id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="content", type="string", example="Updated post content."),
     *             @OA\Property(property="is_edited", type="boolean", example=true),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-17T22:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-17T22:01:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found or not owned by user",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The content field is required.")
     *         )
     *     ),
     *
     * )
     */
    public function update(Request $request, $post_id)
    {
        $post = Post::where('post_id', $post_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'content' => 'required|string',
        ]);

        $post->update([
            'content' => $request->content,
            'is_edited' => true,
        ]);

        return response()->json($post);
    }

    /**
     * @OA\Delete(
     *     path="/posts/{post_id}",
     *     tags={"Posts"},
     *     summary="Soft delete a post",
     *     description="Soft deletes a post owned by the authenticated user.",
     *     operationId="deletePost",
     *     @OA\Parameter(
     *         name="post_id",
     *         in="path",
     *         description="ID of the post",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Post soft deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found or not owned by user",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post not found")
     *         )
     *     ),
     *
     * )
     */
    public function destroy($post_id)
    {
        $post = Post::where('post_id', $post_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $post->delete(); // Soft delete

        return response()->json(null, 204);
    }
}
