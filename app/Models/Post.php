<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'userId',
        'post',
        'tags',
        'category',
        'title',
        'notification'
    ];

    protected $casts = [
        'userId' => 'integer',
        'notification' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
