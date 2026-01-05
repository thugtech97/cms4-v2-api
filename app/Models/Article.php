<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'slug',
        'date',
        'name',
        'contents',
        'json',
        'styles',
        'teaser',
        'status',
        'is_featured',
        'image_url',
        'thumbnail_url',
        'meta_title',
        'meta_keyword',
        'meta_description',
        'user_id',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'is_featured' => 'boolean',
        'json' => 'array',
    ];

    // 🔗 Relationships
    public function category()
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
