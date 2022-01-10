<?php

namespace App\Http\Controllers\Api;

use App\Helpers\APIResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CommentRequest;
use App\Http\Requests\Api\CommentStoreRequest;
use App\Http\Requests\Api\CommentUpdateRequest;
use App\Http\Resources\CommentResourceCollection;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CommentController extends Controller
{
    use APIResponseHelper;

    public function __construct()
    {
        $this->middleware(['api.admin'])->except(['store', 'update']);
        $this->middleware(['auth:sanctum'])->only(['store', 'update']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return CommentResourceCollection
     */
    public function index()
    {
        $comments = Comment::latest()->paginate(10);
        return new CommentResourceCollection($comments);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CommentStoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(CommentStoreRequest $request)
    {
        $validated = $request->validated();
        Comment::create([
            'user_id'   => auth()->user()->id,
            'post_id'   => $validated['post_id'],
            'parent_id' => $validated['parent_id'] ?? 0,
            'text'      => $validated['text'],
        ]);
        return
            $this->send_custom_response(null,
                'Comment created successfully',
                Response::HTTP_CREATED,
                true);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param CommentUpdateRequest $request
     * @param  \App\Models\Comment $comment
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(CommentUpdateRequest $request, Comment $comment)
    {
        $validated = $request->validated();
        $this->authorize('can-edit-comment', $comment);
        $comment->update(['text' => $validated['text']]);
        return
            $this->send_custom_response(null,
                "Comment with id {$comment->id} updated successfully",
                Response::HTTP_OK,
                true);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Comment $comment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Comment $comment)
    {
        $post = $comment->post;
        $postCommentCount = $post->comments()->get()->count();

        $comment->deleteWithReplies();

        $decrementCommentCountAmount = $postCommentCount - $post->comments()->get()->count();
        $post->decrementCommentCount($decrementCommentCountAmount);

        return
            $this->send_custom_response(null,
                "Comment with id {$comment->id} and its replies deleted successfully",
                Response::HTTP_OK,
                true);
    }
}
