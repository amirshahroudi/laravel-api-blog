<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_method()
    {
        $user = User::factory()->user()->create();

        $image = UploadedFile::fake()->image('profile.jpg');
        $response = $this->actingAs($user)
            ->postJson(route('api.upload.profileImage'), ['image' => $image]);
        $profileImageURL = $response['data']['url'];

        $newName = 'amir';

        $data = [
            'name'              => $newName,
            'profile_image_url' => $profileImageURL,
        ];
        $this->actingAs($user)
            ->postJson(route('api.profile.update'), $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => "Profile updated successfully",
                'success' => true,
            ]);
        $user->refresh();
        $this->assertEquals($newName, $user->name);
        $this->assertEquals($profileImageURL, $user->profile_image_url);
        $this->assertFileExists(public_path($user->profile_image_url));
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'auth:sanctum']);
    }

    public function test_update_method_if_user_has_profile_image()
    {
        $user = User::factory()->user()->create();
        //  add image to profile of user
        $oldImage = UploadedFile::fake()->image('old_profile.jpg');
        $oldResponse = $this->actingAs($user)
            ->postJson(route('api.upload.profileImage'), ['image' => $oldImage]);
        $oldProfileImagePath = $oldResponse['data']['url'];
        $user->update([
            'profile_image_url' => $oldProfileImagePath,
        ]);
        // try to new profile image
        $newImage = UploadedFile::fake()->image('new_profile.jpg');
        $newResponse = $this->actingAs($user)
            ->postJson(route('api.upload.profileImage'), ['image' => $newImage]);
        $newProfileImagePath = $newResponse['data']['url'];
        $newName = 'amir';
        $data = [
            'name'              => $newName,
            'profile_image_url' => $newProfileImagePath,
        ];

        $this->assertFileExists(public_path($oldProfileImagePath));

        $this->actingAs($user)
            ->postJson(route('api.profile.update'), $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => "Profile updated successfully",
                'success' => true,
            ]);
        $user->refresh();

        $this->assertEquals($newName, $user->name);
        $this->assertEquals($newProfileImagePath, $user->profile_image_url);
        $this->assertFileExists(public_path($newProfileImagePath));
        $this->assertFileDoesNotExist(public_path($oldProfileImagePath));
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'auth:sanctum']);
    }

    public function test_update_method_if_user_dont_change_profile_image_and_name()
    {
        $user = User::factory()->state(['name' => $name = 'amir'])->user()->create();

        $image = UploadedFile::fake()->image('profile.jpg');
        $response = $this->actingAs($user)
            ->postJson(route('api.upload.profileImage'), ['image' => $image]);
        $profileImageURL = $response['data']['url'];
        $user->update(['profile_image_url' => $profileImageURL]);

        $data = [
            'name'              => $name,
            'profile_image_url' => $profileImageURL,
        ];
        $this->actingAs($user)
            ->postJson(route('api.profile.update'), $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => "Profile updated successfully",
                'success' => true,
            ]);
        $user->refresh();
        $this->assertEquals($name, $user->name);
        $this->assertEquals($profileImageURL, $user->profile_image_url);
        $this->assertFileExists(public_path($user->profile_image_url));
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'auth:sanctum']);
    }

    public function sendRequestForValidationTest($uri, $data, $errors)
    {
        $this->actingAs(User::factory()->user()->create())
            ->postJson($uri, $data)
            ->assertJsonValidationErrors($errors);
    }

    public function test_validation_update_method_required_data()
    {
        $data = [];
        $errors = [
            'name'              => 'The name field is required.',
            'profile_image_url' => 'The profile image url field is required.',
        ];
        $this->sendRequestForValidationTest(route('api.profile.update'), $data, $errors);
    }

    public function test_validation_update_method_name_and_profile_image_url_has_string_rule()
    {
        $data = [
            'name'              => 12345,
            'profile_image_url' => 12345,
        ];
        $errors = [
            'name'              => 'The name must be a string.',
            'profile_image_url' => 'The profile image url must be a string.',
        ];
        $this->sendRequestForValidationTest(route('api.profile.update'), $data, $errors);
    }

    public function test_validation_update_method_name_has_max_255_rule()
    {
        $data = [
            'name' => Str::random(256),
        ];
        $errors = [
            'name' => 'The name must not be greater than 255 characters.',
        ];
        $this->sendRequestForValidationTest(route('api.profile.update'), $data, $errors);
    }

    public function test_validation_update_method_profile_image_url_has_valid_rule()
    {
        $data = [
            'profile_image_url' => Str::random(256),
        ];
        $errors = [
            'profile_image_url' => 'The profile_image_url is not exists.',
        ];
        $this->sendRequestForValidationTest(route('api.profile.update'), $data, $errors);
    }


    public function test_changePassword_method()
    {
        $oldPassword = 'old password';
        $newPassword = 'new password';
        $user = User::factory()->state(['password' => bcrypt($oldPassword)])->user()->create();
        $data = [
            'current_password'          => $oldPassword,
            'new_password'              => $newPassword,
            'new_password_confirmation' => $newPassword,
        ];
        $this->actingAs($user)
            ->postJson(route('api.profile.changePassword'), $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Password changed successfully',
                'success' => true,
            ]);
        $user->refresh();
        $this->assertFalse(Hash::check($oldPassword, $user->password));
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'auth:sanctum']);
    }

    public function test_changePassword_method_with_incorrect_password()
    {
        $oldPassword = 'old password';
        $newPassword = 'new password';
        $user = User::factory()->state(['password' => bcrypt($oldPassword)])->user()->create();
        $data = [
            'current_password'          => Str::random(9),
            'new_password'              => $newPassword,
            'new_password_confirmation' => $newPassword,
        ];
        $this->actingAs($user)
            ->postJson(route('api.profile.changePassword'), $data)
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'message' => 'Password was incorrect',
                'success' => false,
            ]);
        $user->refresh();
        $this->assertTrue(Hash::check($oldPassword, $user->password));
        $this->assertFalse(Hash::check($newPassword, $user->password));
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'auth:sanctum']);
    }

    public function test_validation_changePassword_method_required_data()
    {
        $data = [];
        $errors = [
            'current_password' => 'The current password field is required.',
            'new_password'     => 'The new password field is required.',
        ];
        $this->sendRequestForValidationTest(route('api.profile.changePassword'), $data, $errors);
    }

    public function test_validation_changePassword_method_new_password_has_string_rule()
    {
        $data = [
            'new_password' => 123,
        ];
        $errors = [
            'new_password' => 'The new password must be a string.',
        ];
        $this->sendRequestForValidationTest(route('api.profile.changePassword'), $data, $errors);
    }

    public function test_validation_changePassword_method_new_password_has_confirmed_rule()
    {
        $data = [
            'new_password' => 'new password',
        ];
        $errors = [
            'new_password' => 'The new password confirmation does not match.',
        ];
        $this->sendRequestForValidationTest(route('api.profile.changePassword'), $data, $errors);

        $data = [
            'new_password'              => 'new password',
            'new_password_confirmation' => 'old password',
        ];
        $errors = [
            'new_password' => 'The new password confirmation does not match.',
        ];
        $this->sendRequestForValidationTest(route('api.profile.changePassword'), $data, $errors);
    }

    public function test_validation_changePassword_method_new_password_has_password_default_rule()
    {
        $data = [
            'new_password' => 'new',
        ];
        $errors = [
            'new_password' => 'The new password must be at least 8 characters.',
        ];
        $this->sendRequestForValidationTest(route('api.profile.changePassword'), $data, $errors);
    }
}
