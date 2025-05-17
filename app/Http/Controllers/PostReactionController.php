<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="PostReactions",
 *     description="API endpoints for managing post reactions"
 * )
 */
class PostReactionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/posts/{post_id}/reactions",
     *     tags={"PostReactions"},
     *     summary="Get paginated list of reactions for a post",
     *     description="Returns a paginated list of reactions (LIKE/DISLIKE) for a given post, including user data.",
     *     operationId="getReactionsByPost",
     *     @OA\Parameter(
     *         name="post_id",
     *         in="path",
     *         description="ID of the post",
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
     *         description="Number of reactions per page",
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
     *                     @OA\Property(property="reaction_id", type="integer", example=1),
     *                     @OA\Property(property="post_id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="reaction_type", type="string", enum={"LIKE", "DISLIKE"}, example="LIKE"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-17T22:00:00Z"),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="John Doe")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="total", type="integer", example=30),
     *             @OA\Property(property="last_page", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post not found")
     *         )
     *     ),
     *
     * )
     */
    public function index($post_id)
    {
        $post = Post::findOrFail($post_id); // Ensure post exists

        $reactions = PostReaction::where('post_id', $post_id)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->paginate(15); // Paginate with 15 reactions per page

        return response()->json($reactions);
    }

    /**
     * @OA\Post(
     *     path="/posts/{post_id}/reactions",
     *     tags={"PostReactions"},
     *     summary="Create or update a reaction for a post",
     *     description="Creates or updates a LIKE/DISLIKE reaction for the authenticated user on the specified post.",
     *     operationId="createOrUpdateReaction",
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
     *             @OA\Property(property="reaction_type", type="string", enum={"LIKE", "DISLIKE"}, example="LIKE")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Reaction created or updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="reaction_id", type="integer", example=1),
     *             @OA\Property(property="post_id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="reaction_type", type="string", enum={"LIKE", "DISLIKE"}, example="LIKE"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-17T22:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Post not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The reaction_type field is required.")
     *         )
     *     ),
     *
     * )
     */
    public function store(Request $request, $post_id)
    {
        $request->validate([
            'reaction_type' => 'required|in:LIKE,DISLIKE',
        ]);

        $post = Post::findOrFail($post_id); // Ensure post exists

        $reaction = PostReaction::updateOrCreate(
            [
                'post_id' => $post_id,
                'user_id' => Auth::id(),
            ],
            [
                'reaction_type' => $request->reaction_type,
            ]
        );

        return response()->json($reaction, 201);
    }

    /**
     * @OA\Delete(
     *     path="/posts/{post_id}/reactions",
     *     tags={"PostReactions"},
     *     summary="Soft delete a reaction",
     *     description="Soft deletes the authenticated user's reaction on the specified post.",
     *     operationId="deleteReaction",
     *     @OA\Parameter(
     *         name="post_id",
     *         in="path",
     *         description="ID of the post",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Reaction soft deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reaction not found or not owned by user",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reaction not found")
     *         )
     *     ),
     *
     * )
     */
    public function destroy($post_id)
    {
        $reaction = PostReaction::where('post_id', $post_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $reaction->delete(); // Soft delete

        return response()->json(null, 204);
    }
}
