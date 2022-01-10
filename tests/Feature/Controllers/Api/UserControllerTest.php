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
use Illuminate\Support\Str;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_method()
    {
        $userCount = rand(11, 30);

        for ($i = 0; $i < $userCount; $i++) {
            $this->travel(10 + $i * 10)->minutes();
            User::factory()->create();
        }

        $user = User::latest()->first();
        $userShouldSee['id'] = $user->id;
        $userShouldSee['name'] = $user->name;

        $this->getJson(route('api.user.index'))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data'  => [
                    '*' => ['id', 'name', 'profile_image_url'],
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
            ->assertSee($userShouldSee, false)
            ->assertSee(['total' => $userCount]);


        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    public function test_posts_method()
    {
        User::factory()
            ->count(rand(1, 10))
            ->has(Post::factory()->count(rand(2, 5)))
            ->create();
        $user = User::factory()->create();
        $postCount = rand(11, 20);

        Post::factory()
            ->for($user)
            ->has(Tag::factory()->count(rand(1, 3)))
            ->has(Category::factory()->count(rand(1, 3)))
            ->count($postCount)->create();

        $postShouldSee = Post::where(['user_id' => $user->id])->latest()->first()->toArray();
        unset($postShouldSee['created_at']);
        unset($postShouldSee['updated_at']);
        $this->getJson(route('api.user.posts', $user->id))
            ->assertStatus(Response::HTTP_OK)
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
            ->assertSee($postShouldSee, false)
            ->assertSee(['total' => $postCount]);

        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    public function test_comments_method()
    {
        $commentCount = rand(11, 30);
        $user = User::factory()->create();
        Comment::factory()->for($user)->count($commentCount)->create();
        $commentShouldSee = Comment::where(['user_id' => $user->id])->latest()->first()->toArray();
        unset($commentShouldSee['updated_at']);
        unset($commentShouldSee['created_at']);

        $this->getJson(route('api.user.comments', $user->id))
            ->assertStatus(200)
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
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    public function test_likedPosts_method()
    {
        $user = User::factory()->create();
        $postCount = rand(11, 30);
        Post::factory()->count(rand(1, 10))->create();

        for ($i = 0; $i < $postCount; $i++) {
            $this->travel(10 + $i * 10)->minutes();
            Post::factory()
                ->has(Tag::factory()->count(rand(1, 3)), 'tags')
                ->has(Category::factory()->count(rand(1, 3)), 'categories')
                ->has(Like::factory()->state(['user_id' => $user->id]))
                ->create();
        }
        $likedPostsId = $user->likes()
            ->where(['user_id' => $user->id])
            ->where(['likable_type' => get_class(new Post())])
            ->latest()
            ->get()
            ->pluck('likable_id')
            ->toArray();

        $postShouldSee = Post::find($likedPostsId)->first()->toArray();
        unset($postShouldSee['created_at']);
        unset($postShouldSee['updated_at']);

        $this->getJson(route('api.user.likedPosts', $user->id))
            ->assertStatus(Response::HTTP_OK)
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
            ->assertSee($postShouldSee, false)
            ->assertSee(['total' => $postCount]);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    public function test_promoteUserToAdmin_method_with_correct_password()
    {
        $adminPassword = 'i am an admin';
        $admin = User::factory()->state(['password' => bcrypt($adminPassword)])->admin()->create();
        $user = User::factory()->user()->create();

        $data = [
            'password' => $adminPassword,
        ];

        $this->actingAs($admin)
            ->postJson(route('api.user.promoteToAdmin', $user->id), $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => "User with email {$user->email} promoted to admin",
                'success' => true,
            ]);
        $user->refresh();
        $this->assertEquals(User::ADMIN, $user->type);

        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    public function test_promoteUserToAdmin_method_without_correct_password()
    {
        $adminPassword = 'i am an admin';
        $admin = User::factory()->state(['password' => bcrypt($adminPassword)])->admin()->create();
        $user = User::factory()->user()->create();

        $data = [
            'password' => Str::random(10),
        ];

        $this->actingAs($admin)
            ->postJson(route('api.user.promoteToAdmin', $user->id), $data)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'message' => "Password is incorrect",
                'success' => false,
            ]);
        $user->refresh();
        $this->assertEquals(User::USER, $user->type);

        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    public function test_promoteUserToAdmin_method_with_admin_type()
    {
        $adminPassword = 'i am an admin';
        $admin = User::factory()->state(['password' => bcrypt($adminPassword)])->admin()->create();
        $user = User::factory()->admin()->create();

        $data = [
            'password' => $adminPassword,
        ];

        $this->actingAs($admin)
            ->postJson(route('api.user.promoteToAdmin', $user->id), $data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson([
                'message' => "User with email {$user->email} already has admin type",
                'success' => false,
            ]);
        $user->refresh();
        $this->assertEquals(User::ADMIN, $user->type);

        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    public function test_promoteUserToAdmin_method_validation_required_data()
    {
        $user = User::factory()->user()->create();
        $data = [];
        $errors = [
            'password' => 'The password field is required.',
        ];
        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('api.user.promoteToAdmin', $user->id), $data)
            ->assertJsonValidationErrors($errors);
    }

    public function test_demoteAdminToUser_method_with_correct_password()
    {
        $adminPassword = 'i am an admin';
        $admin = User::factory()->state(['password' => bcrypt($adminPassword)])->admin()->create();
        $user = User::factory()->admin()->create();

        $data = [
            'password' => $adminPassword,
        ];

        $this->actingAs($admin)
            ->postJson(route('api.user.demoteToUser', $user->id), $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => "User with email {$user->email} demote to user",
                'success' => true,
            ]);
        $user->refresh();
        $this->assertEquals(User::USER, $user->type);

        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    public function test_demoteAdminToUser_method_without_correct_password()
    {
        $adminPassword = 'i am an admin';
        $admin = User::factory()->state(['password' => bcrypt($adminPassword)])->admin()->create();
        $user = User::factory()->admin()->create();

        $data = [
            'password' => Str::random(10),
        ];

        $this->actingAs($admin)
            ->postJson(route('api.user.demoteToUser', $user->id), $data)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'message' => "Password is incorrect",
                'success' => false,
            ]);
        $user->refresh();
        $this->assertEquals(User::ADMIN, $user->type);

        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    public function test_demoteAdminToUser_method_with_user_type()
    {
        $adminPassword = 'i am an admin';
        $admin = User::factory()->state(['password' => bcrypt($adminPassword)])->admin()->create();
        $user = User::factory()->user()->create();

        $data = [
            'password' => $adminPassword,
        ];

        $this->actingAs($admin)
            ->postJson(route('api.user.demoteToUser', $user->id), $data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson([
                'message' => "User with email {$user->email} already has user type",
                'success' => false,
            ]);
        $user->refresh();
        $this->assertEquals(User::USER, $user->type);

        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    public function test_demoteAdminToUser_method_validation_required_data()
    {
        $user = User::factory()->admin()->create();
        $data = [];
        $errors = [
            'password' => 'The password field is required.',
        ];
        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('api.user.demoteToUser', $user->id), $data)
            ->assertJsonValidationErrors($errors);
    }

    public function test_adminsList_method()
    {
        $admin = User::factory()->admin()->create();

        User::factory()->user()->count(rand(1, 10))->create();

        $adminCount = rand(11, 30);
        User::factory()->admin()->count($adminCount)->create();

        $user = User::where(['type' => User::ADMIN])->latest()->first();
        $userShouldSee['id'] = $user->id;
        $userShouldSee['name'] = $user->name;

        $user = User::where(['type' => User::USER])->latest()->first();
        $userShouldNotSee['id'] = $user->id;
        $userShouldNotSee['name'] = $user->name;
        $this->actingAs($admin)
            ->getJson(route('api.user.adminsList'))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data'  => [
                    '*' => ['id', 'name', 'profile_image_url'],
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
            ->assertSee($userShouldSee, false)
            ->assertSee(['total' => ($adminCount + 1)])
            ->assertDontSee($userShouldNotSee, false);

        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }
}