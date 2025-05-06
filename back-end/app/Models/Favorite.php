<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'machine_id',
        'component_id',
        'favorite_type',
    ];

    /**
     * Get the machine if this is a machine favorite
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * Get the component if this is a component favorite
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * Scope to get favorites by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get only machine favorites
     */
    public function scopeMachines($query)
    {
        return $query->where('favorite_type', 'machine');
    }

    /**
     * Scope to get only component favorites
     */
    public function scopeComponents($query)
    {
        return $query->where('favorite_type', 'component');
    }

    /**
     * Get the favorited item (machine or component)
     */
    public function getFavoritedItemAttribute()
    {
        if ($this->favorite_type === 'machine') {
            return $this->machine;
        } elseif ($this->favorite_type === 'component') {
            return $this->component;
        }
        
        return null;
    }

    /**
     * Check if the favorite is valid (has associated machine or component)
     */
    public function isValid()
    {
        if ($this->favorite_type === 'machine') {
            return $this->machine_id && $this->machine;
        } elseif ($this->favorite_type === 'component') {
            return $this->component_id && $this->component;
        }
        
        return false;
    }
}