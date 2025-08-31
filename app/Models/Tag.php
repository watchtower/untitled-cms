<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    /**
     * Get all of the pages that are assigned this tag.
     */
    public function pages(): MorphToMany
    {
        return $this->morphedByMany(Page::class, 'taggable');
    }

    /**
     * Get all of the posts that are assigned this tag.
     * (For future use when Blog feature is implemented)
     */
    public function posts()
    {
        // return $this->morphedByMany(Post::class, 'taggable');
    }
}
