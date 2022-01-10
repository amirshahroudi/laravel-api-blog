<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UploadControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploadPostImage_method()
    {
        $image = UploadedFile::fake()->image('post-image.jpg');
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;
        $day = $now->day;

        $path = "posts/images/{$year}/{$month}/{$day}/{$image->hashName()}";

        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('api.upload.postImage'), ['image' => $image])
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data'    => [
                    'url' => $path,
                ],
                'message' => 'Post image uploaded successfully',
                'success' => true,
            ]);
        $this->assertFileExists(public_path($path));
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'api.admin']);
    }

    public function sendRequestForValidationTest(User $user, $uri, array $data, array $errors)
    {
        $this->actingAs($user)
            ->postJson($uri, $data)
            ->assertJsonValidationErrors($errors);

    }

    public function test_validation_required_data_uploadPostImage_method()
    {
        $user = User::factory()->admin()->create();

        $data = [];
        $errors = [
            'image' => 'The image field is required.',
        ];
        $this->sendRequestForValidationTest($user, route('api.upload.postImage'), $data, $errors);
    }

    public function test_validation_uploadPostImage_method_image_has_image_rule()
    {
        $user = User::factory()->admin()->create();

        $data = [
            'image' => UploadedFile::fake()->create('image.txt'),
        ];
        $errors = [
            'image' => 'The image must be an image.',
        ];
        $this->sendRequestForValidationTest($user, route('api.upload.postImage'), $data, $errors);
    }

    public function test_validation_uploadPostImage_method_image_has_max_size_rule()
    {
        $user = User::factory()->admin()->create();

        $data = [
            'image' => UploadedFile::fake()->create('image.png', 1025),
        ];
        $errors = [
            'image' => 'The image must not be greater than 1024 kilobytes.',
        ];
        $this->sendRequestForValidationTest($user, route('api.upload.postImage'), $data, $errors);
    }

    public function test_uploadProfileImage_method()
    {
        $user = User::factory()->create();
        $image = UploadedFile::fake()->image('profile.jpg');
        $path = "profiles/{$image->hashName()}";

        $this->actingAs($user)
            ->postJson(route('api.upload.profileImage'), ['image' => $image])
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data'    => [
                    'url' => $path,
                ],
                'message' => "Profile image for user with email {$user->email} uploaded successfully",
                'success' => true,
            ]);
        $this->assertFileExists(public_path($path));
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'auth:sanctum']);
    }

    public function test_validation_required_data_uploadProfileImage_method()
    {
        $user = User::factory()->create();

        $data = [];
        $errors = [
            'image' => 'The image field is required.',
        ];
        $this->sendRequestForValidationTest($user, route('api.upload.profileImage'), $data, $errors);
    }

    public function test_validation_uploadProfileImage_method_image_has_image_rule()
    {
        $user = User::factory()->create();

        $data = [
            'image' => UploadedFile::fake()->create('image.txt'),
        ];
        $errors = [
            'image' => 'The image must be an image.',
        ];
        $this->sendRequestForValidationTest($user, route('api.upload.profileImage'), $data, $errors);
    }

    public function test_validation_uploadProfileImage_method_image_has_max_size_rule()
    {
        $user = User::factory()->create();

        $data = [
            'image' => UploadedFile::fake()->create('image.png', 257),
        ];
        $errors = [
            'image' => 'The image must not be greater than 256 kilobytes.',
        ];
        $this->sendRequestForValidationTest($user, route('api.upload.profileImage'), $data, $errors);
    }

}
