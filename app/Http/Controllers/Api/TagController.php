<?php

namespace App\Http\Controllers\Api;

use App\Helpers\APIResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TagRequest;
use App\Http\Resources\PostResourceCollection;
use App\Http\Resources\TagResourceCollection;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TagController extends Controller
{
    use APIResponseHelper;

    public function __construct()
    {
        $this->middleware(['api.admin'])->except(['index', 'posts']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tags = Tag::all();
        return $this->send_custom_response(new TagResourceCollection($tags),
            null,
            Response::HTTP_OK,
            true);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param TagRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(TagRequest $request)
    {
        $validated = $request->validated();
        Tag::create([
            'name' => $validated['name'],
        ]);
        return $this->send_custom_response(null,
            'Tag created successfully',
            Response::HTTP_CREATED,
            true);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tag $tag
     * @return PostResourceCollection
     */
    public function posts(Tag $tag)
    {
        $posts = $tag->posts()->latest()->paginate(10);
        return new PostResourceCollection($posts);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param TagRequest $request
     * @param  \App\Models\Tag $tag
     * @return \Illuminate\Http\Response
     */
    public function update(TagRequest $request, Tag $tag)
    {
        $validated = $request->validated();
        $tag->update([
            'name' => $validated['name'],
        ]);
        return $this->send_custom_response(null,
            'Tag updated successfully',
            Response::HTTP_OK,
            true);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tag $tag
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tag $tag)
    {
        $tag->delete();
        return $this->send_custom_response(null,
            "Tag with id {$tag->id} deleted successfully",
            Response::HTTP_OK,
            true);
    }
}
