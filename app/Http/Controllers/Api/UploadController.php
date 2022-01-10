<?php

namespace App\Http\Controllers\Api;

use App\Helpers\APIResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProfileImageRequest;
use App\Http\Requests\Api\UploadPostImageRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UploadController extends Controller
{
    use APIResponseHelper;

    public function __construct()
    {
        $this->middleware('api.admin')->only(['uploadPostImage']);
        $this->middleware('auth:sanctum')->only(['uploadProfileImage']);
    }

    public function uploadPostImage(UploadPostImageRequest $request)
    {
        $image = $request->file('image');
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;
        $day = $now->day;
        $path = "posts/images/{$year}/{$month}/{$day}";
        $image->move(public_path($path), $image->hashName());
        return
            $this->send_custom_response(
                ['url' => $path . '/' . $image->hashName()],
                'Post image uploaded successfully',
                Response::HTTP_OK,
                true
            );
    }

    public function uploadProfileImage(ProfileImageRequest $request)
    {
        $image = $request->file('image');
        $user = auth()->user();
        $path = "profiles";
        $image->move(public_path($path), $image->hashName());
        return
            $this->send_custom_response(
                ['url' => $path . '/' . $image->hashName()],
                "Profile image for user with email {$user->email} uploaded successfully",
                Response::HTTP_OK,
                true
            );
    }
}
