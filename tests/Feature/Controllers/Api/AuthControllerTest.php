<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_method()
    {
        $user = User::factory()->make();
        $data = [
            'name'                  => $user->name,
            'email'                 => $user->email,
            'password'              => $password = Str::random(10),
            'password_confirmation' => $password,
        ];
        $this->postJson(route('api.register'), $data)
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'message' => "User with email {$data['email']} created successfully",
                'success' => true,
            ]);
        $shouldSee = [
            'name'  => $data['name'],
            'email' => $data['email'],
        ];
        $this->assertDatabaseHas('users', $shouldSee);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    public function test_login_method()
    {
        User::factory()->count(rand(3, 10))->create();

        $user = User::factory()->state(['password' => bcrypt('123456789')])->create();

        $data = ['email' => $user->email, 'password' => '123456789'];

        $this->postJson(route('api.login'), $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => ['token'],
                'success',
            ])
            ->assertJson(['success' => true]);
        $this->assertEquals($user->id, Auth::id());
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    public function test_login_method_with_incorrect_password()
    {
        User::factory()->count(rand(3, 10))->create();

        $user = User::factory()->state(['password' => bcrypt('123456789')])->create();

        $data = ['email' => $user->email, 'password' => 'incorrect password'];

        $this->postJson(route('api.login'), $data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson([
                "message" => "The given data was invalid.",
                "errors"  => [
                    "email" => [
                        "These credentials do not match our records.",
                    ],
                ],
            ]);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    public function test_each_user_must_have_only_one_token_after_each_login()
    {
        $user = User::factory()->state(['password' => bcrypt('123456789')])->create();
        $data = ['email' => $user->email, 'password' => '123456789'];

        $this->postJson(route('api.login'), $data)
            ->assertStatus(Response::HTTP_OK);
        $this->postJson(route('api.login'), $data)
            ->assertStatus(Response::HTTP_OK);
        $this->postJson(route('api.login'), $data)
            ->assertStatus(Response::HTTP_OK);

        $this->assertCount(1, $user->tokens);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    /**
     * @param array $data
     * @param array $errors
     */
    public function sendAuthRequestForValidation($uri, array $data, array $errors)
    {
        $this->postJson($uri, $data)
            ->assertJsonValidationErrors($errors);
    }

    public function test_register_validation_required_data()
    {
        $data = [];
        $errors = [
            'name'     => 'The name field is required.',
            'email'    => 'The email field is required.',
            'password' => 'The password field is required.',
        ];
        $this->sendAuthRequestForValidation(route('api.register'), $data, $errors);
    }

    public function test_register_validation_name_and_email_have_string_rule()
    {
        $data = [
            'name'  => 12,
            'email' => 34,
        ];
        $errors = [
            'name'  => 'The name must be a string.',
            'email' => 'The email must be a string.',
        ];
        $this->sendAuthRequestForValidation(route('api.register'), $data, $errors);
    }

    public function test_register_validation_name_and_email_have_max_255_rule()
    {
        $data = [
            'name'  => Str::random(256),
            'email' => Str::random(256),
        ];
        $errors = [
            'name'  => 'The name must not be greater than 255 characters.',
            'email' => 'The email must not be greater than 255 characters.',
        ];
        $this->sendAuthRequestForValidation(route('api.register'), $data, $errors);
    }

    public function test_register_validation_email_has_email_rule()
    {
        $data = [
            'email' => 'amir',
        ];
        $errors = [
            'email' => 'The email must be a valid email address.',
        ];
        $this->sendAuthRequestForValidation(route('api.register'), $data, $errors);
    }

    public function test_register_validation_email_has_unique_rule()
    {
        User::factory()->state(['email' => 'amir@email.com'])->create();
        $data = [
            'email' => 'amir@email.com',
        ];
        $errors = [
            'email' => 'The email has already been taken.',
        ];
        $this->sendAuthRequestForValidation(route('api.register'), $data, $errors);
    }

    public function test_register_validation_password_has_confirmed_rule()
    {
        $data = [
            'password' => 123456789,
        ];
        $errors = [
            'password' => 'The password confirmation does not match.',
        ];
        $this->sendAuthRequestForValidation(route('api.register'), $data, $errors);

        $data = [
            'password'              => 123456789,
            'password_confirmation' => 45678,
        ];
        $this->sendAuthRequestForValidation(route('api.register'), $data, $errors);
    }

    public function test_register_validation_password_has_password_default_rule()
    {
        $data = [
            'password' => 1234567,
        ];
        $errors = [
            'password' => 'The password must be at least 8 characters.',
        ];
        $this->sendAuthRequestForValidation(route('api.register'), $data, $errors);
    }

    public function test_login_validation_required_data()
    {
        $data = [];
        $errors = [
            'email'    => 'The email field is required.',
            'password' => 'The password field is required.',
        ];
        $this->sendAuthRequestForValidation(route('api.login'), $data, $errors);
    }

    public function test_login_validation_email_and_password_have_string_rule()
    {
        $data = [
            'email'    => 1234,
            'password' => 4321,
        ];
        $errors = [
            'email'    => 'The email must be a string.',
            'password' => 'The password must be a string.',
        ];
        $this->sendAuthRequestForValidation(route('api.login'), $data, $errors);
    }

    public function test_login_validation_email_has_email_rule()
    {
        $data = [
            'email' => 'amir',
        ];
        $errors = [
            'email' => 'The email must be a valid email address.',
        ];
        $this->sendAuthRequestForValidation(route('api.login'), $data, $errors);
    }

    public function test_forgotPassword_method()
    {
        Notification::fake();

        $user = User::factory()->create();
        $email = $user->email;
//        $token = Password::broker()->createToken($user);

        $this->postJson(route('api.password.forgot'), ['email' => 'test@email.com'])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson([
                'message' => "We can't find a user with that email address.",
                'success' => false,
            ]);

        $this->postJson(route('api.password.forgot'), ['email' => $email])
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => "Reset password emailed to {$email}",
                'success' => true,
            ]);

        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertCount(1, DB::select('select * from password_resets where email = :email', ['email' => $email]));
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    public function test_forgotPassword_validation()
    {
        $data = [];
        $errors = [
            'email' => 'The email field is required.',
        ];
        $this->sendAuthRequestForValidation(route('api.password.forgot'), $data, $errors);

        $data = ['email' => 124];
        $errors = [
            'email' => 'The email must be a string.',
        ];
        $this->sendAuthRequestForValidation(route('api.password.forgot'), $data, $errors);

        $data = ['email' => 'test'];
        $errors = [
            'email' => 'The email must be a valid email address.',
        ];
        $this->sendAuthRequestForValidation(route('api.password.forgot'), $data, $errors);
    }

    public function test_resetPassword_method()
    {
        $oldPassword = '123456789';
        $user = User::factory()
            ->state(['password' => bcrypt($oldPassword)])
            ->create();
        $token = Password::broker()->createToken($user);

        $data = [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => $newPassword = 'new password',
            'password_confirmation' => $newPassword,
        ];

        $this->postJson(route('api.password.reset'), $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => "Password for {$user->email} updated",
                'success' => true,
            ]);
        $user->refresh();
        $this->assertFalse(Hash::check($oldPassword, $user->password));
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    public function test_resetPassword_method_invalid_token()
    {
        $oldPassword = '123456789';
        $user = User::factory()
            ->state(['password' => bcrypt($oldPassword)])
            ->create();
        $token = Password::broker()->createToken($user);
        $token[5] = 's';
        $token[6] = '5';

        $data = [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => $newPassword = 'new password',
            'password_confirmation' => $newPassword,
        ];

        $this->postJson(route('api.password.reset'), $data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson([
                'message' => 'This password reset token is invalid.',
                'success' => false,
            ]);
        $user->refresh();
        $this->assertTrue(Hash::check($oldPassword, $user->password));
        $this->assertFalse(Hash::check($newPassword, $user->password));
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api']);
    }

    public function test_resetPassword_validation_required_data()
    {
        $data = [];
        $errors = [
            'token'    => 'The token field is required.',
            'email'    => 'The email field is required.',
            'password' => 'The password field is required.',
        ];
        $this->sendAuthRequestForValidation(route('api.password.reset'), $data, $errors);
    }

    public function test_resetPassword_validation_email_has_email_rule()
    {
        $data = [
            'email' => 'this is not email',
        ];
        $errors = [
            'email' => 'The email must be a valid email address.',
        ];
        $this->sendAuthRequestForValidation(route('api.password.reset'), $data, $errors);
    }

    public function test_resetPassword_validation_password_has_string_rule()
    {
        $data = [
            'password' => 123456789,
        ];
        $errors = [
            'password' => 'The password must be a string.',
        ];
        $this->sendAuthRequestForValidation(route('api.password.reset'), $data, $errors);
    }

    public function test_resetPassword_validation_password_has_confirmed_rule()
    {
        $data = [
            'password' => '123456789',
        ];
        $errors = [
            'password' => 'The password confirmation does not match.',
        ];
        $this->sendAuthRequestForValidation(route('api.password.reset'), $data, $errors);

        $data = [
            'password'              => '123456789',
            'password_confirmation' => '12345678901',
        ];
        $errors = [
            'password' => 'The password confirmation does not match.',
        ];
        $this->sendAuthRequestForValidation(route('api.password.reset'), $data, $errors);

    }

    public function test_resetPassword_validation_password_has_password_default_rule()
    {
        $data = [
            'password' => '12345',
        ];
        $errors = [
            'password' => 'The password must be at least 8 characters.',
        ];
        $this->sendAuthRequestForValidation(route('api.password.reset'), $data, $errors);
    }

    public function test_logout_method()
    {
        //todo test maybe incomplete
        $password = '123456789';
        $user = User::factory()->state(['password' => bcrypt($password)])->create();
        $data = [
            'email'    => $user->email,
            'password' => $password,
        ];
        $this->postJson(route('api.login'), $data)
            ->assertStatus(Response::HTTP_OK);
        $this->actingAs($user)
            ->postJson(route('api.logout'))
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Logged out successfully',
                'success' => true,
            ]);
        $user->refresh();
        $this->assertCount(0, $user->tokens);
        $this->assertEquals(request()->route()->gatherMiddleware(), ['api', 'auth:sanctum']);
    }
}
