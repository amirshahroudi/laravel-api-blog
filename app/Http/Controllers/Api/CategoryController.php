<?php

namespace App\Http\Controllers\Api;

use App\Helpers\APIResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CategoryRequest;
use App\Http\Resources\CategoryResourceCollection;
use App\Http\Resources\PostResourceCollection;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoryController extends Controller
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
        $categories = Category::all();
        return
            $this->send_custom_response(
                new CategoryResourceCollection($categories),
                null,
                Response::HTTP_OK,
                true);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CategoryRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(CategoryRequest $request)
    {
        Category::create([
            'name'      => $request->input('name'),
            'parent_id' => $request->input('parent_id') ?? 0,
        ]);
        return
            $this->send_custom_response(null,
                'Category created successfully',
                Response::HTTP_CREATED,
                true);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Category $category
     * @return PostResourceCollection
     */
    public function posts(Category $category)
    {
        $posts = $category->posts()->latest()->paginate(10);
        return new PostResourceCollection($posts);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param CategoryRequest $request
     * @param  \App\Models\Category $category
     * @return \Illuminate\Http\Response
     */
    public function update(CategoryRequest $request, Category $category)
    {
        $validated = $request->validated();
        $category->update([
            'name'      => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? 0,
        ]);
        return $this->send_custom_response(null,
            'Category updated successfully',
            Response::HTTP_OK,
            true);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        $category->deleteAndSetChildParent();
        return $this->send_custom_response(null,
            "category with id {$category->id} deleted successfully",
            Response::HTTP_OK,
            true);
    }
}
