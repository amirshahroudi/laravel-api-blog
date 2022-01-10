<?php

namespace Tests\Feature\Exceptions;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class HandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_exception()
    {
        $response = $this->postJson(route('api.profile.changePassword'));
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->status());
        $this->assertEquals('Unauthenticated, first you should login', $response['message']);
        $this->assertFalse($response['success']);
    }

    public function test_unauthorized_exception()
    {
        $response = $this->actingAs(User::factory()->user()->create())
            ->postJson(route('api.upload.postImage'));
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->status());
        $this->assertEquals('Unauthorized, you cannot access this route', $response['message']);
        $this->assertFalse($response['success']);
    }

    public function test_model_not_found_exception()
    {
        Post::factory()->create();
        $response = $this->getJson(route('api.post.show', 2));
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->status());
        $this->assertEquals('Not found', $response['message']);
        $this->assertFalse($response['success']);
    }

    public function test_not_found_http_exception()
    {
        $response = $this->getJson('/api/12345');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->status());
        $this->assertEquals('Not found', $response['message']);
        $this->assertFalse($response['success']);
    }
}
