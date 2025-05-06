<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'model',
        'sap_number',
        'description',
        'image_path',
        'company',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all components for this machine
     */
    public function components(): HasMany
    {
        return $this->hasMany(Component::class);
    }

    /**
     * Get all drawings for this machine
     */
    public function drawings(): HasMany
    {
        return $this->hasMany(MachineDrawing::class);
    }

    /**
     * Get all favorites for this machine
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class)
                    ->where('favorite_type', 'machine');
    }

    /**
     * Scope to search machines by name or model
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('model', 'like', "%{$search}%")
              ->orWhere('sap_number', 'like', "%{$search}%");
        });
    }

    /**
     * Get the main drawing image for the machine
     */
    public function getMainDrawingAttribute()
    {
        return $this->drawings()->where('drawing_type', 'exploded')->first();
    }
}