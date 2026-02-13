<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    /**
     * Mass assignable attributes.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'title',
        'content',
        'category_id',
        'user_id',
        'published_at',
    ];

    /**
     * Post belongs to a category.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Post belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}