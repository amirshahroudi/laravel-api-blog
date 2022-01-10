<?php

namespace Tests\Feature\Controllers\Api;

use App\Http\Resources\PostResource;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use PHPUnit\Util\Json;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    //todo i dont know it's enough or not
    public function test_index_method()
    {
        $postCount = rand(1, 20);
        for ($i = 0; $i < $postCount; $i++) {
            $this->travel(10 + $i * 10)->minutes();
            Post::factory()->create();
        }

        $firstPost = Post::latest()->first()->toArray();
        unset($firstPost['updated_at']);
        unset($firstPost['created_at']);

        $response = $this->getJson(route('api.post.index'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data'  => [
                    '*' => [
                        'id', 'user_id', 'title', 'description', 'created_at', 'like_count', 'comment_count',
                        'tags', 'categories',
                    ],
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
                'meta'  => [
                    'current_page',
                    'from',
                    'last_page', 'links',
                    'path',
                    'per_page',
                    'to',
                    'total',
                ],
            ])
            ->assertSee($firstPost, false)
            ->assertSee(['total' => $postCount]);
        //if title include special character like I can't. need to escape be false
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    public function test_store_method()
    {
        $tagCount = rand(1, 5);
        $categoryCount = rand(1, 5);

        $post = Post::factory()->make()->toArray();

        unset($post['user_id']);

        $tags = Tag::factory()->count($tagCount)->create();
        $categories = Category::factory()->count($categoryCount)->create();

        $data = array_merge(
            $post,
            ['tags' => $tags->pluck('id')->toArray()],
            ['categories' => $categories->pluck('id')->toArray()]
        );

        $response = $this->actingAs($user = User::factory()->admin()->create())
            ->postJson(route('api.post.store'), $data)
            ->assertJson(['message' => 'post created successfully', 'success' => true]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('posts', $post);

        $this->assertEquals($user->id, Post::where($post)->first()->user->id);

        $this->assertEquals(
            $tags->pluck('id')->toArray(),
            Post::where($post)->first()->tags->pluck('id')->toArray());

        $this->assertEquals(
            $categories->pluck('id')->toArray(),
            Post::where($post)->first()->categories->pluck('id')->toArray()
        );
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    public function test_update_method()
    {
        $user = User::factory()->admin()->create();

        $futureTagCount = rand(1, 5);
        $futureCategoryCount = rand(1, 5);

        $futureTags = Tag::factory()->count($futureTagCount)->create();
        $futureCategories = Category::factory()->count($futureCategoryCount)->create();

        $futurePost = Post::factory()->make()->toArray();
        unset($futurePost['user_id']);

        $data = array_merge(
            $futurePost,
            ['tags' => $futureTags->pluck('id')->toArray()],
            ['categories' => $futureCategories->pluck('id')->toArray()]
        );

        $post = Post::factory()
            ->for($user)
            ->has(Tag::factory()->count(rand(1, 5)))
            ->has(Category::factory()->count(rand(1, 5)))
            ->create();
        $futurePost['id'] = $post->id;

        $response = $this->actingAs($user)
            ->patchJson(route('api.post.update', $post->id), $data)
            ->assertJson(['message' => 'post updated successfully', 'success' => true]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('posts', $futurePost);

        $this->assertEquals(
            $futureTags->pluck('id')->toArray(),
            Post::where($futurePost)->first()->tags->pluck('id')->toArray());

        $this->assertEquals(
            $futureCategories->pluck('id')->toArray(),
            Post::where($futurePost)->first()->categories->pluck('id')->toArray()
        );
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    /**
     * @param $user
     * @param array $data
     * @param array $errors
     */
    public function sendRequestForValidationTest(array $data, array $errors)
    {
        $user = User::factory()->admin()->create();
        //store method
        $this->actingAs($user)
            ->postJson(route('api.post.store'), $data)
            ->assertJsonValidationErrors($errors);
        //update method
        $this->actingAs($user)
            ->patchJson(
                route('api.post.update', Post::factory()->create()->id),
                $data)
            ->assertJsonValidationErrors($errors);
    }

    public function test_validation_required_data()
    {
        $data = [];
        $errors = [
            'title'       => 'The title field is required.',
            'description' => 'The description field is required.',
            'tags'        => 'The tags field is required.',
            'categories'  => 'The categories field is required.',
        ];
        $this->sendRequestForValidationTest($data, $errors);

    }

    public function test_validation_title_and_description_have_string_rule()
    {
        $data = [
            'title'       => 123,
            'description' => true,
        ];
        $errors = [
            'title'       => 'The title must be a string.',
            'description' => 'The description must be a string.',
        ];
        $this->sendRequestForValidationTest($data, $errors);
    }

    public function test_validation_title_and_description_have_min_rule()
    {
        $data = [
            'title'       => 'exam',
            'description' => 'Amir',
        ];
        $errors = [
            'title'       => 'The title must be at least 5 characters.',
            'description' => 'The description must be at least 10 characters.',
        ];
        $this->sendRequestForValidationTest($data, $errors);
    }

    public function test_validation_tags_and_categories_have_array_rule()
    {
        $data = [
            'tags'       => 1,
            'categories' => 2,
        ];
        $errors = [
            'tags'       => 'The tags must be an array.',
            'categories' => 'The categories must be an array.',
        ];
        $this->sendRequestForValidationTest($data, $errors);
    }

    public function test_validation_tags_and_categories_have_exists_in_own_table_rule()
    {
        $data = [
            'tags'       => [-1],
            'categories' => [-2],
        ];
        $errors = [
            'tags'       => 'The selected tags is invalid.',
            'categories' => 'The selected categories is invalid.',
        ];
        $this->sendRequestForValidationTest($data, $errors);
    }

    public function test_show_method()
    {
        //todo add comments to this test
        $user = User::factory()->admin()->create();

        $tagsCount = rand(1, 5);
        $categoriesCount = rand(1, 5);
        $likeCount = rand(1, 10);
        $commentCount = rand(1, 10);

        $tags = Tag::factory()->count($tagsCount)->create();
        $categories = Category::factory()->count($categoriesCount)->create();

        $post = Post::factory()
            ->for($user)
            ->has(Comment::factory()->count($commentCount))
            ->create();

        for ($i = 0; $i < $likeCount; $i++) {
            $post->like(User::factory()->create());
        }
        $post->incrementCommentCount($commentCount);

        $post->tags()->sync($tags->pluck('id')->toArray());
        $post->categories()->sync($categories->pluck('id')->toArray());
        $response = $this->getJson(route('api.post.show', $post->id))
            ->assertJson([
                'data'    => [
                    'id'            => $post->id,
                    'user_id'       => $post->user->id,
                    'title'         => $post->title,
                    'description'   => $post->description,
                    'created_at'    => (string)$post->created_at,
                    'like_count'    => $likeCount,
                    'comment_count' => $commentCount,
                    'tags'          => $tags->pluck('name')->toArray(),
                    'categories'    => $categories->pluck('name')->toArray(),
                ],
                'success' => true,
            ])
            ->assertStatus(Response::HTTP_OK);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    public function test_destroy_method()
    {
        $user = User::factory()->admin()->create();
        $post = Post::factory()
            ->for($user)
            ->has(Tag::factory()->count(rand(1, 5)))
            ->has(Category::factory()->count(rand(1, 5)))
            ->has(Comment::factory()->count(rand(10, 15)))
            ->has(Like::factory()->count(rand(10, 15)))
            ->create();
        $postId = $post->id;


        $this->actingAs($user)
            ->deleteJson(route('api.post.destroy', $postId))
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => "post with id $postId deleted successfully",
                'success' => true,
            ]);

        $this->assertDeleted($post)
            ->assertDeleted('comments', ['post_id' => $postId])
            ->assertDeleted('likes', ['likable_id' => $postId, 'likable_type' => get_class($post)]);

        $this->assertEmpty($post->tags);
        $this->assertEmpty($post->categories);

        $this->assertEmpty(DB::select('select * from posts where id = :postId', ['postId' => $postId]));
        $this->assertEmpty(DB::select('select * from comments where post_id = :postId', ['postId' => $postId]));
        $this->assertEmpty(
            DB::select(
                'select * from likes where likable_id = :postId and likable_type = :postType',
                ['postId' => $postId, 'postType' => get_class(new Post())])
        );
        $this->assertEmpty(DB::select('select * from category_post where post_id = :postId', ['postId' => $postId]));
        $this->assertEmpty(DB::select('select * from post_tag where post_id = :postId', ['postId' => $postId]));

        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    public function test_comments_method()
    {
        $post = Post::factory()->create();
        $commentCount = rand(11, 20);
        $comments = [];
        for ($i = 0; $i < $commentCount; $i++) {
            $this->travel(10 + ($i * 10))->hours();
            $comments = array_merge($comments, array(Comment::factory()->for($post)->create()->toArray()));
        }
        $comments = Comment::where(['post_id' => $post->id])
            ->latest()
            ->get(['id', 'user_id', 'post_id', 'parent_id', 'text'])
            ->toArray();

        $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('api.post.comments', $post->id))
            ->assertStatus(Response::HTTP_OK)
            ->assertSee($comments[0])
            ->assertSee($comments[5])
            ->assertSee($comments[9])
            ->assertSee(['total' => $commentCount]);
        //todo assertDontSee
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    public function test_like_method()
    {
        $post = Post::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api.post.like', $post->id))
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => "post with id: {$post->id} liked successfully by user with id: {$user->id}",
                          'success' => true,
            ]);
        $post->refresh();
        $this->assertCount(1, $post->likes);
        $this->assertEquals(1, $post->like_count);
        $this->assertEquals($user->id, $post->likes->first()->user->id);

        $this->actingAs($user)
            ->postJson(route('api.post.like', $post->id))
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => "post with id: $post->id has already been liked by user with id: $user->id",
                          'success' => false,
            ]);
        $post->refresh();
        $this->assertCount(1, $post->likes);
        $this->assertEquals(1, $post->like_count);
        $this->assertEquals($user->id, $post->likes->first()->user->id);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'auth:sanctum']);
    }

    public function test_unlike_method()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)->postJson(route('api.post.like', $post->id));
        $post->refresh();

        $this->actingAs($user)
            ->postJson(route('api.post.unlike', $post->id))
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => "post with id: {$post->id} unliked successfully by user with id: {$user->id}",
                          'success' => true,
            ]);

        $post->refresh();
        $this->assertCount(0, $post->likes);
        $this->assertEquals(0, $post->like_count);

        $this->actingAs($user)
            ->postJson(route('api.post.unlike', $post->id))
            ->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => "user with id: {$user->id} didnt like post with id: {$post->id} before",
                          'success' => false,
            ]);

        $post->refresh();
        $this->assertCount(0, $post->likes);
        $this->assertEquals(0, $post->like_count);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'auth:sanctum']);
    }
}
