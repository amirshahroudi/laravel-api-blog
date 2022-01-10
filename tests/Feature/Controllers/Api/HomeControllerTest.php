<?php

namespace Tests\Feature\Controllers\Api;

use App\Helpers\APIResponseHelper;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use  RefreshDatabase;

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
//        dd(array_merge(Post::all()->toArray(), ['first ' => $firstPost], ['latest' => Post::latest()->paginate(10)->toArray()]));
        $response = $this->getJson(route('api.post.index'));
        $response->assertStatus(200)
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
            ->assertSee($firstPost, false)
            ->assertSee(['total' => $postCount]);
        //if title include special character like I can't. need to escape be false
    }
}

