<?php

namespace Tests\Feature\Middlewares\Api;

use App\Exceptions\Handler;
use App\Http\Middleware\Api\APICheckUserIsAdmin;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
//use Illuminate\Http\Request;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery\Container;
use Tests\TestCase;

class APICheckUserIsAdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_user_is_not_admin_when_logged_in()
    {
        $user = User::factory()->user()->create();
        $this->actingAs($user);
        $request = Request::create('/api', 'GET');
        $middleware = new APICheckUserIsAdmin();
        $is_authorizationException = false;
        try {
            $response = $middleware->handle($request, function () {
            });
        } catch (AuthorizationException $e) {
            $is_authorizationException = true;
        }
        $this->assertTrue($is_authorizationException);
    }

    public function test_when_user_is_admin_when_logged_in()
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);
        $request = Request::create('/api', 'GET');
        $middleware = new APICheckUserIsAdmin();
        $is_authorizationException = false;
        $response = 'hi';
        try {
            $response = $middleware->handle($request, function () {
            });
        } catch (AuthorizationException $e) {
            $is_authorizationException = true;
        }
        $this->assertFalse($is_authorizationException);
        $this->assertEquals(null, $response);
    }

    public function test_when_user_not_logged_in()
    {

        $request = Request::create('/api', 'GET');
        $middleware = new APICheckUserIsAdmin();
        $is_authorizationException = false;
        try {
            $middleware->handle($request, function () {
            });
        } catch (AuthorizationException $e) {
            $is_authorizationException = true;
        }
        $this->assertTrue($is_authorizationException);
    }

}
