<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_id',
        'image_path',
        'alt_text',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the component that owns this image
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * Scope to get only primary images
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Set this image as primary and unset others
     */
    public function setPrimary()
    {
        // Unset other primary images for this component
        $this->component->images()->update(['is_primary' => false]);
        
        // Set this as primary
        $this->update(['is_primary' => true]);
    }

    /**
     * Get the full URL for this image
     */
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->image_path);
    }
}