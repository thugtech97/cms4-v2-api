<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LayoutPreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'thumbnail',
        'content',
        'is_active',
    ];
}
