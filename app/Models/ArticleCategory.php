<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArticleCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'user_id',
    ];

    // 🔗 Relationships
    public function articles()
    {
        return $this->hasMany(Article::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
