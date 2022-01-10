<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id'     => User::factory(),
            'title'       => $this->faker->realTextBetween(5, 10),
            //            'title'       => "I get\".",
            //            'title'       => 'I can \'t.',
            'description' => $this->faker->text,
            //            'like_count'    => 0,
            //            'comment_count' => 0,
        ];
    }
}
