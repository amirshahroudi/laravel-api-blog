<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Category extends Model
{
    use HasFactory;
    protected $fillable = ['parent_id', 'name'];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function child()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }

    public function deleteAndSetChildParent()
    {
        foreach ($this->child()->get() as $item) {
            $item->update(['parent_id' => 0]);
        }
        $this->delete();
    }
}
