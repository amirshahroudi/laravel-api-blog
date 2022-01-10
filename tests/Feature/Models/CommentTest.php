<?php

namespace Tests\Feature\Models;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase, ModelTestingHelper;

    protected function model(): Model
    {
        return new Comment();
    }

    public function test_comment_relationship_with_post()
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()
            ->for($post)
            ->create();
        $this->assertTrue(isset($comment->post_id));
        $this->assertTrue($comment->post instanceof Post);
        $this->assertEquals($post->id, $comment->post_id);
        $this->assertEquals($post->id, $comment->post->id);
    }

    public function test_comment_relationship_with_user()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()
            ->for($user)
            ->create();
        $this->assertTrue(isset($comment->user_id));
        $this->assertTrue($comment->user instanceof User);
        $this->assertEquals($user->id, $comment->user_id);
        $this->assertEquals($user->id, $comment->user->id);
    }

    public function test_comment_can_has_parent()
    {
        $comment = Comment::factory()->create();
        $reply = Comment::factory()
            ->for($comment, 'parent')
            ->create();
        $this->assertTrue(isset($reply->parent_id));
        $this->assertEquals($reply->parent_id, $reply->parent->id);
        $this->assertEquals($comment->id, $reply->parent->id);
        $this->assertTrue($reply->parent instanceof Comment);
    }

    public function test_comment_can_has_reply()
    {
        $count = rand(1, 10);
        $comment = Comment::factory()
            ->has(Comment::factory()->count($count), 'replies')
            ->create();
        $this->assertCount($count, $comment->replies);
        $this->assertTrue($comment->replies->first() instanceof Comment);
        $this->assertEquals($comment->id, $comment->replies->first()->parent->id);
    }

    public function test_comment_relation_with_like()
    {
        $count = rand(1, 10);
        $comment = Comment::factory()
            ->has(Like::factory()->count($count))
            ->create();
        $this->assertTrue($comment->likes->first() instanceof Like);
        $this->assertCount($count, $comment->likes);
        $this->assertEquals($comment->id, $comment->likes()->first()->likable_id);
        $this->assertEquals(Comment::class, get_class($comment->likes()->first()->likable));
    }

    public function test_deleteWithReplies_method()
    {

        $comment = Comment::factory()
            ->has(
                Comment::factory()
                    ->has(Comment::factory()->count(rand(1, 2)), 'replies')
                    ->count(rand(1, 10)),
                'replies')
            ->create();
        $allCommentId = Comment::all(['id'])->toArray();

        $commentCount = rand(5, 15);
        $commentsShouldSee = Comment::factory()->count($commentCount)->create()->toArray();

        $comment->deleteWithReplies();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        foreach ($allCommentId as $item) {
            $this->assertDatabaseMissing('comments', $item);
        }

        $this->assertDatabaseCount('comments', $commentCount);

        $this->assertDatabaseHas('comments', $commentsShouldSee[0]);
        $this->assertDatabaseHas('comments', $commentsShouldSee[$commentCount - 1]);
    }
}
