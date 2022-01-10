<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Post::factory(10)->create();
        $categoryCount = rand(1, 10);
        $categoryChildCount = rand(1, 3);

        Category::factory()
            ->has(Category::factory()->count($categoryChildCount), 'child')
            ->count($categoryCount)
            ->create();
        // \App\Models\User::factory(10)->create();
    }
}
