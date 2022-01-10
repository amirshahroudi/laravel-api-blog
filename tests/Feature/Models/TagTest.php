<?php

namespace Tests\Feature\Models;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase, ModelTestingHelper;

    public function test_tag_relationship_with_post()
    {
        $count = rand(1, 10);
        $tag = Tag::factory()
            ->has(Post::factory()->count($count))
            ->create();
        $this->assertCount($count, $tag->posts);
        $this->assertTrue($tag->posts->first() instanceof Post);
    }

    protected function model(): Model
    {
        return new Tag();
    }
}
