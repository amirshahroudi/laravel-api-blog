<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase, ModelTestingHelper;

    protected function model(): Model
    {
        return new Post();
    }

    public function test_post_relationship_with_user()
    {
        $user = User::factory()->create();
        $post = Post::factory()
            ->for($user)
            ->create();

        $this->assertTrue(isset($post->user_id));
        $this->assertTrue($post->user instanceof User);
        $this->assertEquals($post->user->id, $post->user_id);
        $this->assertEquals($post->user->id, $user->id);
    }

    public function test_post_relationship_with_tag()
    {
        $count = rand(1, 10);
        $post = Post::factory()
            ->has(Tag::factory()->count($count))
            ->create();
        $this->assertCount($count, $post->tags);
        $this->assertTrue($post->tags->first() instanceof Tag);
        $this->assertCount($count, DB::select('select * from post_tag where post_id = :postId', ['postId' => $post->id]));
    }

    public function test_post_relation_with_comment()
    {
        $count = rand(1, 10);
        $post = Post::factory()
            ->has(Comment::factory()->count($count))
            ->create();
        $this->assertCount($count, $post->comments);
        $this->assertTrue($post->comments->first() instanceof Comment);
        $this->assertCount($count, DB::select('select * from comments where post_id = :postId', ['postId' => $post->id]));
    }

    public function test_post_relationship_with_category()
    {
        $count = rand(1, 10);
        $post = Post::factory()
            ->has(Category::factory()->count($count))
            ->create();
        $this->assertTrue($post->categories->first() instanceof Category);
        $this->assertCount($count, $post->categories);
    }

    public function test_post_relation_with_like()
    {
        $count = rand(1, 10);
        $post = Post::factory()
            ->has(Like::factory()->count($count))
            ->create();
        $this->assertCount($count, $post->likes);
        $this->assertEquals($post->id, $post->likes->first()->likable_id);
        $this->assertTrue($post->likes->first() instanceof Like);
        $this->assertCount(
            $count,
            DB::select(
                'select * from likes where likable_id = :postId and likable_type = :postType',
                ['postId' => $post->id, 'postType' => get_class(new Post())])
        );
    }

    public function test_like_method()
    {
        $count = rand(1, 10);
        $post = Post::factory()->create();
        for ($i = 0; $i < $count; $i++) {
            $post->like(User::factory()->create());
        }
        $post->refresh();

        $this->assertCount($count, $post->likes);
        $this->assertEquals($post->id, $post->likes->first()->likable_id);
        $this->assertTrue($post->likes->first() instanceof Like);
        $this->assertCount(
            $count,
            DB::select(
                'select * from likes where likable_id = :postId and likable_type = :postType',
                ['postId' => $post->id, 'postType' => get_class(new Post())])
        );
        $this->assertEquals($count, Post::find($post->id)->first()->like_count);

        $user = User::factory()->create();

        $liked = $post->like($user);
        $this->assertTrue($liked);

        $liked = $post->like($user);
        $this->assertFalse($liked);

        $liked = $post->like($user);
        $this->assertFalse($liked);

        $liked = $post->like($user);
        $this->assertFalse($liked);

        $count++;
        $post->refresh();

        $this->assertCount($count, $post->likes);
        $this->assertCount(
            $count,
            DB::select(
                'select * from likes where likable_id = :postId and likable_type = :postType',
                ['postId' => $post->id, 'postType' => get_class(new Post())])
        );
        $this->assertEquals($count, Post::find($post->id)->first()->like_count);
    }

    public function test_unlike_method()
    {
        $this->withExceptionHandling();
        $count = rand(1, 10);
        $post = Post::factory()->create();
        for ($i = 0; $i < $count; $i++) {
            $post->like(User::factory()->create());
        }

        $user = User::factory()->create();

        $like = $post->unlike($user);
        $this->assertFalse($like);

        $post->refresh();
        $this->assertCount($count, $post->likes);

        $like = $post->like($user);
        $this->assertTrue($like);

        $count++;
        $post->refresh();
        $this->assertCount($count, $post->likes);

        $like = $post->unlike($user);
        $this->assertTrue($like);
        $count--;
        $post->refresh();

        $this->assertCount($count, $post->likes);
        $this->assertCount(
            $count,
            DB::select(
                'select * from likes where likable_id = :postId and likable_type = :postType',
                ['postId' => $post->id, 'postType' => get_class(new Post())])
        );
        $this->assertEquals($count, Post::find($post->id)->first()->like_count);
    }

    public function test_incrementCommentCount_method()
    {
        $post = Post::factory()->create();
        $this->assertEquals(0, $post->comment_count);
        $post->incrementCommentCount();
        $this->assertEquals(1, $post->comment_count);
        $post->incrementCommentCount(2);
        $this->assertEquals(3, $post->comment_count);
    }

    public function test_decrementCommentCount_method()
    {
        $post = Post::factory()->create();
        $this->assertEquals(0, $post->comment_count);
        $post->decrementCommentCount();
        $this->assertEquals(0, $post->comment_count);
        $post->incrementCommentCount(9);
        $post->decrementCommentCount(3);
        $post->decrementCommentCount();
        $this->assertEquals(5, $post->comment_count);
    }
}
