<?php

namespace Tests\Feature\Models;

use App\Http\Controllers\Api\CategoryController;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase, ModelTestingHelper;

    protected function model(): Model
    {
        return new Category();
    }

    public function test_category_can_has_parent()
    {
        $parent = Category::factory()->create();
        $category = Category::factory()
            ->for($parent, 'parent')
            ->create();

        $this->assertTrue(isset($category->parent_id));
        $this->assertEquals($parent->id, $category->parent->id);
        $this->assertEquals($parent->id, $category->parent_id);
        $this->assertTrue($category->parent instanceof Category);
    }

    public function test_category_can_has_child()
    {
        $count = rand(1, 10);
        $category = Category::factory()
            ->has(Category::factory()->count($count), 'child')
            ->create();
        $this->assertCount($count, $category->child);
        $this->assertTrue($category->child->first() instanceof Category);
        $this->assertEquals($category->id, $category->child->first()->parent->id);
    }

    public function test_category_relation_with_post()
    {
        $count = rand(1, 10);
        $category = Category::factory()
            ->has(Post::factory()->count($count))
            ->create();
        $this->assertCount($count, $category->posts);
        $this->assertTrue($category->posts->first() instanceof Post);
    }

    public function test_deleteAndSetChildParent_method()
    {
        $childCount = 7;
        $category = Category::factory()
            ->has(Category::factory()->count($childCount), 'child')
            ->create();

        $childs = $category->child()->get(['id', 'name', 'parent_id']);
        $shouldSee = [];
        foreach ($childs->toArray() as $child) {
            $child['parent_id'] = 0;
            $shouldSee = array_merge($shouldSee, [$child]);
        }

        $category->deleteAndSetChildParent();

        $this->assertDatabaseMissing('categories', $category->toArray());
        $this->assertEmpty(Category::where(['parent_id' => $category->id])->get());
        $this->assertDatabaseMissing('categories', ($childs->toArray())[0]);
        $this->assertDatabaseMissing('categories', ($childs->toArray())[array_key_last($childs->toArray())]);
        $this->assertDatabaseHas('categories', $shouldSee[0]);
        $this->assertDatabaseHas('categories', $shouldSee[array_key_last($shouldSee)]);
        $this->assertDatabaseCount('categories', $childCount);
        $this->assertCount($childCount, Category::where(['parent_id' => 0])->get());
        $this->assertCount($childCount, $shouldSee);

        $singleCategory = Category::factory()->create();
        $singleCategory->deleteAndSetChildParent();
        $this->assertDatabaseMissing('categories', $singleCategory->toArray());

    }
}
