<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_method()
    {
        $tagCount = rand(1, 10);
        $tags = Tag::factory()->count($tagCount)->create()->pluck(['id', 'name'])->toArray();

        $this->getJson(route('api.tag.index'))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
                'success',
            ])
            ->assertSee($tags[0], false)
            ->assertSee($tags[rand(0, array_key_last($tags))], false)
            ->assertSee($tags[array_key_last($tags)], false);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    public function test_store_method()
    {
        $user = User::factory()->admin()->create();
        $data = Tag::factory()->make()->toArray();

        $this->actingAs($user)
            ->postJson(route('api.tag.store'), $data)
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'message' => 'Tag created successfully',
                'success' => true,
            ]);
        $this->assertDatabaseHas('tags', $data);
        $this->assertDatabaseCount('tags', 1);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    public function test_update_method()
    {
        $user = User::factory()->admin()->create();
        $tag = Tag::factory()->create();
        $data = Tag::factory()->make()->toArray();

        $missing['id'] = $tag->id;
        $missing['name'] = $tag->name;

        $data['id'] = $tag->id;

        $this->actingAs($user)
            ->patchJson(route('api.tag.update', $tag->id), $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Tag updated successfully',
                'success' => true,
            ]);
        $this->assertDatabaseHas('tags', $data);
        $this->assertDatabaseMissing('tags', $missing);

        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    public function sendRequestForValidationTest(array $data, array $errors)
    {
        $user = User::factory()->admin()->create();
        //store method
        $this->actingAs($user)
            ->postJson(route('api.tag.store'), $data)
            ->assertJsonValidationErrors($errors);
        //update method
        $this->actingAs($user)
            ->patchJson(route('api.tag.update', Tag::factory()->create()->id), $data)
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

    public function test_validation_unique_rule()
    {
        $tag = Tag::factory()->create();
        $name = $tag->name;
        $data = [
            'name' => $name,
        ];
        $errors = [
            'name' => 'The name has already been taken.',
        ];
        $this->sendRequestForValidationTest($data, $errors);
    }

    public function test_posts_method()
    {
        $postCount = rand(11, 20);
        Tag::factory()->count(rand(1, 10))->create();
        $tag = Tag::factory()
            ->has(Post::factory()->count($postCount))
            ->create();

        $postShouldSee = $tag->posts()->latest()->first()->toArray();
        unset($postShouldSee['created_at']);
        unset($postShouldSee['updated_at']);
        unset($postShouldSee['pivot']);

        $this->getJson(route('api.tag.posts', $tag->id))
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
        $user = User::factory()->admin()->create();
        $postCount = rand(11, 20);
        Tag::factory()->count(rand(1, 10))->create();
        $tag = Tag::factory()
            ->has(Post::factory()->count($postCount))
            ->create();

        $this->actingAs($user)
            ->deleteJson(route('api.tag.destroy', $tag->id))
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => "Tag with id {$tag->id} deleted successfully",
                'success' => true,
            ]);
        $this->assertEmpty(Tag::where(['id' => $tag->id])->get());
        $this->assertDeleted('tags', ['id' => $tag->id]);
        $this->assertDatabaseCount('posts', $postCount);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }
}
