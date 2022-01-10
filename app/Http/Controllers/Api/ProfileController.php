<?php

namespace App\Http\Controllers\Api;

use App\Helpers\APIResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\ProfileUpdateRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    use APIResponseHelper;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function update(ProfileUpdateRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();
        if ($validated['profile_image_url'] != $user->profile_image_url) {
            if (File::exists(public_path($user->profile_image_url))) {
                File::delete(public_path($user->profile_image_url));
            }
        }

        auth()->user()->update([
            'name'              => $validated['name'],
            'profile_image_url' => $validated['profile_image_url'],
        ]);

        return
            $this->send_custom_response(null,
                "Profile updated successfully",
                Response::HTTP_OK,
                true);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();
        if (Hash::check($validated['current_password'], $user->password)) {
            $user->update([
                'password'       => bcrypt($validated['new_password']),
                'remember_token' => Str::random(60),
            ]);
            event(new PasswordReset($user));

            return
                $this->send_custom_response(null,
                    'Password changed successfully',
                    Response::HTTP_OK,
                    true);
        }
        return
            $this->send_custom_response(null,
                'Password was incorrect',
                Response::HTTP_UNAUTHORIZED,
                false);
    }
}
