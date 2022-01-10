<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'title', 'description'];
    protected $guarded = ['comment_count', 'like_count'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likable');
    }

    /**
     * @param User $user
     * @return bool
     * if like successfully return true,
     * if the post has been liked by user return false
     */
    public function like(User $user)
    {
        if (!$this->likes()->where('user_id', '=', $user->id)->first()) {
            $this->likes()->create(['user_id' => $user->id]);
            $this->increment('like_count');
            return true;
        }
        return false;
    }

    /**
     * @param User $user
     * @return bool
     * if unlike successfully return true,
     * if post didnt like by user then unlike is unsuccessful, return false
     */
    public function unlike(User $user)
    {
        if (!!$like = $this->likes()->where('user_id', '=', $user->id)->first()) {
            $like->delete();
            $this->decrement('like_count');
            return true;
        }
        return false;
    }

    public function incrementCommentCount(int $amount = null)
    {
        $this->increment('comment_count', $amount ?? 1);
    }

    public function decrementCommentCount(int $amount = null)
    {
        if ($this->comment_count - ($amount ?? 0) > 0) {
            $this->decrement('comment_count', $amount ?? 1);
        }
    }
}
