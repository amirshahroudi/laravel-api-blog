<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;
    protected $fillable = ['id', 'user_id', 'likable_id', 'likable_type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likable()
    {
        return $this->morphTo();
    }
}
