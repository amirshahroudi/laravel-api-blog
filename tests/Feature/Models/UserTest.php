<?php

namespace Tests\Feature\Models;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase, ModelTestingHelper;

    public function test_user_relationship_with_post()
    {
        $count = rand(1, 10);
        $user = User::factory()
            ->has(Post::factory()->count($count))
            ->create();
        $this->assertCount($count, $user->posts);
        $this->assertTrue($user->posts->first() instanceof Post);
    }

    public function test_user_relationship_with_comment()
    {
        $count = rand(1, 10);
        $user = User::factory()
            ->has(Comment::factory()->count($count))
            ->create();
        $this->assertCount($count, $user->comments);
        $this->assertTrue($user->comments->first() instanceof Comment);
    }

    public function test_user_relation_with_like()
    {
        $count = rand(1, 10);
        $user = User::factory()
            ->has(Like::factory()->count($count))
            ->create();
        $this->assertCount($count, $user->likes);
        $this->assertTrue($user->likes->first() instanceof Like);
//        $this->assertTrue(isset($user->likes->first()->user_id));
//        $this->assertEquals($user->id, $user->likes->first()->user_id));
    }

    protected function model(): Model
    {
        return new User();
    }
}
