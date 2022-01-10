<?php

namespace App\Http\Controllers\Api;

use App\Helpers\APIResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResourceCollection;
use App\Http\Resources\PostResourceCollection;
use App\Http\Resources\UserResourceCollection;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use APIResponseHelper;

    public function __construct()
    {
        $this->middleware('api.admin')->only(['adminsList', 'promoteToAdmin', 'demoteToUser']);
    }

    public function index()
    {
        $users = User::latest()->paginate(10);
        return new UserResourceCollection($users);
    }

    public function adminsList()
    {
        $admins = User::where(['type' => User::ADMIN])->latest()->paginate(10);
        return new UserResourceCollection($admins);
    }

    public function posts(User $user)
    {
        $posts = $user->posts()->latest()->paginate(10);
        return new PostResourceCollection($posts);
    }

    public function comments(User $user)
    {
        $comments = $user->comments()->latest()->paginate(10);
        return new CommentResourceCollection($comments);
    }

    public function likedPosts(User $user)
    {
        $likedPostsId = $user->likes()
            ->where(['user_id' => $user->id])
            ->where(['likable_type' => get_class(new Post())])
            ->latest()
            ->get()
            ->pluck('likable_id')
            ->toArray();
        $posts = Post::whereIn('id', $likedPostsId)->paginate(10);
        return new PostResourceCollection($posts);
    }

    public function promoteToAdmin(User $user, Request $request)
    {
        $validated = $request->validate([
            'password' => 'required',
        ]);

        if (Hash::check($validated['password'], auth()->user()->getAuthPassword())) {
            if ($user->type == User::ADMIN) {
                return
                    $this->send_custom_response(null,
                        "User with email {$user->email} already has admin type",
                        Response::HTTP_UNPROCESSABLE_ENTITY,
                        false);
            }
            $user->update(['type' => User::ADMIN]);
            return
                $this->send_custom_response(null,
                    "User with email {$user->email} promoted to admin",
                    Response::HTTP_OK,
                    true);
        }
        return
            $this->send_custom_response(null,
                "Password is incorrect",
                Response::HTTP_UNAUTHORIZED,
                false);
    }

    public function demoteToUser(User $user, Request $request)
    {
        $validated = $request->validate([
            'password' => 'required',
        ]);
        if (Hash::check($validated['password'], auth()->user()->getAuthPassword())) {
            if ($user->type == User::USER) {
                return
                    $this->send_custom_response(null,
                        "User with email {$user->email} already has user type",
                        Response::HTTP_UNPROCESSABLE_ENTITY,
                        false);
            }
            $user->update(['type' => User::USER]);
            return
                $this->send_custom_response(null,
                    "User with email {$user->email} demote to user",
                    Response::HTTP_OK,
                    true);
        }
        return
            $this->send_custom_response(null,
                "Password is incorrect",
                Response::HTTP_UNAUTHORIZED,
                false);
    }
}
