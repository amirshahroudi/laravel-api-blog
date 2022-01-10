<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_index_method()
    {
        $categoryCount = rand(1, 10);
        $categoryChildCount = rand(1, 3);

        Category::factory()
            ->has(Category::factory()->count($categoryChildCount), 'child')
            ->count($categoryCount)
            ->create()
            ->toArray();

        $categories = Category::all(['id', 'name', 'parent_id'])->toArray();

        $this->getJson(route('api.category.index'))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'parent_id'],
                ],
                'success',
            ])
            ->assertSee($categories[0], false)
            ->assertSee($categories[rand(1, array_key_last($categories))], false)
            ->assertSee($categories[array_key_last($categories)], false);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    public function test_store_method()
    {
        $user = User::factory()->admin()->create();
        $data = Category::factory()->make()->toArray();

        $this->actingAs($user)
            ->postJson(route('api.category.store'), $data)
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'message' => 'Category created successfully',
                'success' => true,
            ]);
        $this->assertDatabaseHas('categories', $data);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    public function test_store_method_with_parent_id()
    {
        $user = User::factory()->admin()->create();
        Category::factory()->create();

        $parent_id = Category::first()->id;

        $child = Category::factory()->state(['parent_id' => $parent_id])->make()->toArray();

        $this->actingAs($user)
            ->postJson(route('api.category.store'), $child)
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'message' => 'Category created successfully',
                'success' => true,
            ]);
        $this->assertDatabaseHas('categories', $child);
        $this->assertEquals(Category::find($parent_id)->first()->child->first()->id, Category::where($child)->first()->id);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    public function test_store_method_without_set_parent_id()
    {
        $user = User::factory()->admin()->create();
        //test if parent_id not set
        $data = Category::factory()->make()->toArray();
        unset($data['parent_id']);
        $this->actingAs($user)
            ->postJson(route('api.category.store'), $data)
            ->assertStatus(Response::HTTP_CREATED);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    public function test_update_method()
    {
        $user = User::factory()->admin()->create();

        $category = Category::factory()->create();

        $missing['name'] = $category->name;
        $missing['parent_id'] = $category->parent_id;


        $data = Category::factory()->make()->toArray();
        $this->actingAs($user)
            ->patchJson(route('api.category.update', $category->id), $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Category updated successfully',
                'success' => true,
            ]);
        $data['id'] = $category->id;
        $this->assertDatabaseHas('categories', $data);
        $this->assertDatabaseMissing('categories', $missing);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    public function test_update_method_without_parent_id_set()
    {
        $user = User::factory()->admin()->create();
        $parent_id = Category::factory()->create()->id;
        $category = Category::factory()
            ->state(['parent_id' => $parent_id])
            ->create();

        $this->assertEquals($parent_id, $category->parent_id);
        //test if parent_id not set
        $data = Category::factory()->make()->toArray();
        unset($data['parent_id']);
        $this->actingAs($user)
            ->patchJson(route('api.category.update', $category->id), $data)
            ->assertStatus(Response::HTTP_OK);
        $category->refresh();
        $this->assertEquals(0, $category->parent_id);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    /**
     * @param array $data
     * @param array $errors
     */
    public function sendRequestForValidationTest(array $data, array $errors)
    {
        $user = User::factory()->admin()->create();
        //store method
        $this->actingAs($user)
            ->postJson(route('api.category.store'), $data)
            ->assertJsonValidationErrors($errors);
        //update method
        $this->actingAs($user)
            ->patchJson(route('api.category.update', Category::factory()->create()->id), $data)
            ->assertJsonValidationErrors($errors);
    }

    public function test_validation_required_data()
    {
        $data = [];
        $errors = [
            'name' => 'The name field is required.',
        ];
        $this->sendRequestForValidationTest($data, $errors);
    }

    public function test_validation_parent_id_has_integer_rule()
    {
        $data = [
            'name'      => 'Movie',
            'parent_id' => 'Hi',
        ];
        $errors = [
            'parent_id' => 'The parent id must be an integer.',
        ];
        $this->sendRequestForValidationTest($data, $errors);
    }

    public function test_validation_parent_id_has_exists_rule()
    {
        $data = [
            'name'      => 'Books',
            'parent_id' => 1,
        ];
        $errors = [
            'parent_id' => 'The parent_id is not exists in database',
        ];
        $this->sendRequestForValidationTest($data, $errors);
    }

    public function test_posts_method()
    {
        Category::factory()->count(rand(1, 10))->create();
        Post::factory()->count(rand(1, 10))->create();

        $postCount = rand(1, 30);
        $category = Category::factory()->create();
        $posts = [];

        for ($i = 0; $i < $postCount; $i++) {
            $this->travel(10 + $i * 10)->minutes();

            $post = Post::factory()->create();

            $post->categories()->sync($category);

            $posts = array_merge($posts, [$post->toArray()]);
        }

        $postShouldSee = $category->posts()->latest()->first()->toArray();

        unset($postShouldSee['created_at']);
        unset($postShouldSee['updated_at']);
        unset($postShouldSee['pivot']);

        $this->getJson(route('api.category.posts', $category->id))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data'  => [
                    '*' => ['id', 'user_id', 'title', 'description', 'created_at', 'tags', 'categories'],
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
            ->assertSee($postShouldSee, false)
            ->assertSee(['total' => $postCount]);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    public function test_destroy_method()
    {
        $postCount = rand(11, 20);
        $user = User::factory()->admin()->create();
        $category = Category::factory()
            ->has(Category::factory()->count(rand(1, 10)), 'child')
            ->has(Post::factory()->count($postCount))
            ->create();
        $this->actingAs($user)
            ->deleteJson(route('api.category.destroy', $category->id))
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => "category with id {$category->id} deleted successfully",
                'success' => true,
            ]);
        $this->assertEmpty(Category::where(['parent_id' => $category->id])->get());
        $this->assertDeleted('categories', ['id' => $category->id]);
        $this->assertDatabaseCount('posts', $postCount);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }
}