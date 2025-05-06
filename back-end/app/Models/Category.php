<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image_path',
    ];

    /**
     * Get all components in this category
     */
    public function components(): HasMany
    {
        return $this->hasMany(Component::class);
    }

    /**
     * Scope to search categories
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Get components count for this category
     */
    public function getComponentsCountAttribute()
    {
        return $this->components()->count();
    }
}