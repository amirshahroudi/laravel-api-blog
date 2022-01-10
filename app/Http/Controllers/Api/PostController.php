<?php

namespace App\Http\Controllers\Api;

use App\Helpers\APIResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PostRequest;
use App\Http\Resources\CommentResourceCollection;
use App\Http\Resources\PostResource;
use App\Http\Resources\PostResourceCollection;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    use APIResponseHelper;

    public function __construct()
    {
        $this->middleware(['api.admin'])->except(['index', 'show', 'comments', 'like', 'unlike']);
        $this->middleware(['auth:sanctum'])->only(['like', 'unlike']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return PostResourceCollection
     */
    public function index()
    {
        $posts = Post::latest()->paginate(10);
        return new PostResourceCollection($posts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param PostRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(PostRequest $request)
    {
        $validated = $request->validated();
        $post = auth()->user()->posts()->create([
            'title'       => $validated['title'],
            'description' => $validated['description'],
        ]);
        $post->tags()->sync($validated['tags']);
        $post->categories()->sync($validated['categories']);
        return
            $this->send_custom_response(null,
                'post created successfully',
                Response::HTTP_CREATED,
                true);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Post $post
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        return
            $this->send_custom_response(
                new PostResource($post),
                null,
                Response::HTTP_OK,
                true
            );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param PostRequest $request
     * @param  \App\Models\Post $post
     * @return \Illuminate\Http\Response
     */
    public function update(PostRequest $request, Post $post)
    {
        $validated = $request->validated();
        $post->update([
            'title'       => $validated['title'],
            'description' => $validated['description'],
        ]);
        $post->tags()->sync($validated['tags']);
        $post->categories()->sync($validated['categories']);
        return
            $this->send_custom_response(null,
                'post updated successfully',
                Response::HTTP_OK,
                true);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Post $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        //detach post tags
        //detach post categories
        //delete post comment
        //delete post like
        //delete post

        $post->tags()->detach();
        $post->categories()->detach();
        $post->comments()->delete();
        //no need to delete and detach because onDeleteCascade in Migrations

        $post->likes()->delete();
        $post->delete();

        return
            $this->send_custom_response(null,
                "post with id $post->id deleted successfully",
                Response::HTTP_OK,
                true);
    }

    public function comments(Post $post)
    {
        $comments = $post->comments()->latest()->paginate(10);
        return new CommentResourceCollection($comments);

    }

    public function like(Post $post)
    {
        $user = auth()->user();
        $like = $post->like($user);

        return
            $like ?
                $this->send_custom_response(null,
                    "post with id: {$post->id} liked successfully by user with id: {$user->id}",
                    Response::HTTP_OK,
                    true)
                :
                $this->send_custom_response(null,
                    "post with id: {$post->id} has already been liked by user with id: {$user->id}",
                    Response::HTTP_OK,
                    false);
    }

    public function unlike(Post $post)
    {
        $user = auth()->user();
        $unlike = $post->unlike($user);
        return
            $unlike ?
                $this->send_custom_response(null,
                    "post with id: {$post->id} unliked successfully by user with id: {$user->id}",
                    Response::HTTP_OK,
                    true)
                :
                $this->send_custom_response(null,
                    "user with id: {$user->id} didnt like post with id: {$post->id} before",
                    Response::HTTP_OK,
                    false);

    }
}
