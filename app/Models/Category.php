<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    /**
     * Get all of the pages that are assigned this category.
     */
    public function pages(): MorphToMany
    {
        return $this->morphedByMany(Page::class, 'categorizable');
    }

    /**
     * Get all of the posts that are assigned this category.
     * (For future use when Blog feature is implemented)
     */
    public function posts()
    {
        // return $this->morphedByMany(Post::class, 'categorizable');
    }
}
