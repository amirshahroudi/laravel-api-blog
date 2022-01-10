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

class LikeTest extends TestCase
{
    use RefreshDatabase, ModelTestingHelper;

    public function test_like_relation_with_user()
    {
        $user = User::factory()->create();
        $like = Like::factory()
            ->for($user)
            ->create();
        $this->assertTrue(isset($like->user_id));
        $this->assertTrue($like->user instanceof User);
        $this->assertEquals($like->user_id, $like->user->id);
        $this->assertEquals($user->id, $like->user->id);

    }

    public function test_like_relation_with_post()
    {
        $post = Post::factory()->create();
        $like = Like::factory()
            ->for($post,'likable')
            ->create();
//        dd(array_merge($like->toArray(), ['likeall' => Like::all()->toArray()], ['postall' => Post::all()->toArray()]));
        $this->assertTrue(isset($like->likable_id));
        $this->assertTrue(isset($like->likable_type));
        $this->assertTrue($like->likable instanceof Post);
        $this->assertEquals($like->likable->id, $like->likable_id);
        $this->assertEquals(get_class($like->likable), $like->likable_type);
        $this->assertEquals(Post::class, get_class($like->likable));
        $this->assertEquals($post->id, $like->likable->id);
    }

    public function test_like_relation_with_comment()
    {
        $comment = Comment::factory()->create();
        $like = Like::factory()
            ->for($comment, 'likable')
            ->create();
        $this->assertTrue(isset($like->likable_id));
        $this->assertTrue(isset($like->likable_type));
        $this->assertTrue($like->likable instanceof Comment);
        $this->assertEquals($like->likable->id, $like->likable_id);
        $this->assertEquals(get_class($like->likable), $like->likable_type);
        $this->assertEquals(Comment::class, get_class($like->likable));
        $this->assertEquals($comment->id, $like->likable->id);
    }

    protected function model(): Model
    {
        return new Like();
    }
}
