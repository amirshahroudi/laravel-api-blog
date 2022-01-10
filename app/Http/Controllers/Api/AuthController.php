<?php

namespace App\Http\Controllers\Api;

use App\Helpers\APIResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use APIResponseHelper;

    public function __construct()
    {
        $this->middleware('auth:sanctum')->only(['logout']);
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();
        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);
        event(new Registered($user));
        return
            $this->send_custom_response(null,
                "User with email {$user->email} created successfully",
                Response::HTTP_CREATED,
                true);
    }

    public function login(LoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = \auth()->user();

        $user->tokens()->delete();

        $token = $user->createToken('authtoken')->plainTextToken;

        return
            $this->send_custom_response(
                ['token' => $token],
                null,
                Response::HTTP_OK,
                true);
    }

    public function logout()
    {
        \auth()->user()->tokens()->delete();
        return
            $this->send_custom_response(null,
                'Logged out successfully',
                Response::HTTP_OK,
                true);

    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $validated = $request->validated();
        $email = $validated['email'];

        $status = Password::sendResetLink(['email' => $email]);

        return $status == Password::RESET_LINK_SENT
            ? $this->send_custom_response(null,
                "Reset password emailed to {$email}",
                Response::HTTP_OK,
                true)
            : $this->send_custom_response(null,
                "We can't find a user with that email address.",
                Response::HTTP_UNPROCESSABLE_ENTITY,
                false);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $validated = $request->validated();
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($validated) {
                $user->forceFill([
                    'password'       => Hash::make($validated['password']),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status == Password::PASSWORD_RESET
            ? $this->send_custom_response(null,
                "Password for {$validated['email']} updated",
                Response::HTTP_OK,
                true)
            : $this->send_custom_response(null,
                'This password reset token is invalid.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                false);
    }
}
