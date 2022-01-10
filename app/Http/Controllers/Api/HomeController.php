<?php

namespace App\Http\Controllers\Api;

use App\Helpers\APIResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResourceCollection;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HomeController extends Controller
{
    use APIResponseHelper;

    public function index()
    {
        $posts = Post::paginate(10);
        return new PostResourceCollection($posts);
    }
}
