<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_method()
    {
        $user = User::factory()->admin()->create();
        $commentCount = rand(11, 30);

        for ($i = 0; $i < $commentCount; $i++) {
            $this->travel(10 + $i * 10)->minutes();
            Comment::factory()->create();
        }
        $commentShouldSee = Comment::latest()->first()->toArray();
        unset($commentShouldSee['updated_at']);
        unset($commentShouldSee['created_at']);
        $response = $this
            ->actingAs($user)
            ->getJson(route('api.comment.index'));
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data'  => [
                    '*' => ['id', 'user_id', 'post_id', 'parent_id', 'text', 'created_at'],
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
            ->assertSee($commentShouldSee, false)
            ->assertSee(['total' => $commentCount]);
        //if title include special character like I can't. need to escape be false
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    public function test_store_method()
    {
        $user = User::factory()->create();
        $parent = Comment::factory()->create();

        $data = Comment::factory()
            ->for($user)
            ->state(['parent_id' => $parent->id])
            ->state(['post_id' => $parent->post->id])
            ->make()->toArray();

        $this->actingAs($user)
            ->postJson(route('api.comment.store'), $data)
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'message' => 'Comment created successfully',
                'success' => true,
            ]);
        $this->assertDatabaseHas('comments', $data);
        $this->assertEquals($user->id, Comment::where($data)->first()->user->id);
        $this->assertEquals($parent->post->id, Comment::where($data)->first()->post->id);
        $this->assertEquals($parent->replies->first()->id, Comment::where($data)->first()->id);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'auth:sanctum']);
    }

    public function test_store_method_without_parent_id()
    {
        $user = User::factory()->create();

        $data = Comment::factory()
            ->for($user)
            ->make()->toArray();
        unset($data['parent_id']);

        $this->actingAs($user)
            ->postJson(route('api.comment.store'), $data)
            ->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('comments', $data);
        $this->assertEquals($user->id, Comment::where($data)->first()->user->id);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'auth:sanctum']);
    }

    public function test_update_method()
    {
        $user = User::factory()->create();
        $parent = Comment::factory()->create();
        $comment = Comment::factory()
            ->for($user)
            ->state(['parent_id' => $parent->id])
            ->state(['post_id' => $parent->post->id])
            ->create()->toArray();

        $data = Comment::factory()->make()->toArray();
        $data = ['text' => $data['text']];

        $this->actingAs($user)
            ->patchJson(route('api.comment.update', $comment['id']), $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => "Comment with id {$comment['id']} updated successfully",
                'success' => true,
            ]);

        $shouldHas['id'] = $comment['id'];
        $shouldHas['user_id'] = $comment['user_id'];
        $shouldHas['post_id'] = $comment['post_id'];
        $shouldHas['parent_id'] = $comment['parent_id'];
        $shouldHas['text'] = $data['text'];

        $this->assertDatabaseHas('comments', $shouldHas);
        $this->assertEquals($user->id, Comment::where(['id' => $shouldHas['id']])->first()->user->id);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'auth:sanctum']);
    }

    public function test_update_method_when_user_is_not_admin_or_owner_of_comment()
    {
        $comment = Comment::factory()->create();
        $user = User::factory()->user()->create();
        $data = ['text' => 'amir'];
        $this->actingAs($user)
            ->patchJson(route('api.comment.update', $comment->id), $data)
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'auth:sanctum']);
    }

    public function test_validation_required_data_store_method()
    {
        $data = [];
        $errors = [
            'text'    => 'The text field is required.',
            'post_id' => 'The post id field is required.',
        ];
        $user = User::factory()->admin()->create();


        $this->actingAs($user)
            ->postJson(route('api.comment.store'), $data)
            ->assertJsonValidationErrors($errors);
    }

    public function test_validation_required_data_update_method()
    {
        $user = User::factory()->admin()->create();
        $data = [];
        $errors = [
            'text' => 'The text field is required.',
        ];
        //update method
        $this->actingAs($user)
            ->patchJson(
                route('api.comment.update', Comment::factory()->create()->id),
                $data)
            ->assertJsonValidationErrors($errors);
    }

    public function test_validation_post_id_has_exists_rule_in_store_method()
    {
        $data = [
            'post_id' => 100,
        ];
        $errors = [
            'post_id' => 'The selected post id is invalid.',
        ];
        $user = User::factory()->admin()->create();


        $this->actingAs($user)
            ->postJson(route('api.comment.store'), $data)
            ->assertJsonValidationErrors($errors);
    }

    public function test_validation_parent_id_has_exists_rule_if_not_zero_in_store_method()
    {
        $data = [
            'parent_id' => 100,
        ];
        $errors = [
            'parent_id' => 'The parent_id is not exists in database.',
        ];
        $user = User::factory()->admin()->create();


        $this->actingAs($user)
            ->postJson(route('api.comment.store'), $data)
            ->assertJsonValidationErrors($errors);
    }

    public function test_destroy_method()
    {
        $user = User::factory()->admin()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()
            ->for($post)
            ->has(
                Comment::factory()
                    ->for($post)
                    ->has(Comment::factory()->for($post)->count(rand(1, 2)), 'replies')
                    ->count(rand(1, 10)),
                'replies')
            ->create();

        $shouldMissingCommentsId = Comment::all(['id'])->toArray();

        $commentCount = rand(5, 15);
        $commentsShouldSee = Comment::factory()->for($post)->count($commentCount)->create()->toArray();

        $postCommentCountBeforeDeleteComment = Comment::where(['post_id' => $post->id])->get()->count();

        $this->assertCount($postCommentCountBeforeDeleteComment, $post->comments);

        $post->incrementCommentCount($postCommentCountBeforeDeleteComment);


        $this->actingAs($user)
            ->deleteJson(route('api.comment.destroy', $comment->id))
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => "Comment with id {$comment->id} and its replies deleted successfully",
                'success' => true,
            ]);
        $post->refresh();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        foreach ($shouldMissingCommentsId as $item) {
            $this->assertDatabaseMissing('comments', $item);
        }

        $this->assertDatabaseCount('comments', $commentCount);

        $this->assertDatabaseHas('comments', $commentsShouldSee[0]);
        $this->assertDatabaseHas('comments', $commentsShouldSee[$commentCount - 1]);

        $this->assertEquals($commentCount, $post->comment_count);

        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }
}