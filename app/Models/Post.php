<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    // These fields can be mass-assigned (like with Post::create)
    protected $fillable = ['title', 'content', 'category_id'];

    // Relationship: each post belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}