<?php

namespace App\Models;

use App\Models\Media;
use App\Traits\Draft;
use App\Traits\Likeable;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use Likeable, Draft;

    protected $guarded = [];

    protected $appends = ['thumb'];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getImageAttribute($image)
    {
        return $image ?? Media::url(Media::NO_IMAGE_URL);
    }

    public function getThumbAttribute()
    {
        return Media::thumb($this->image);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }
}
