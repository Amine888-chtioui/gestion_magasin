<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineDrawing extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'title',
        'file_path',
        'drawing_type',
        'page_number',
        'clickable_areas',
    ];

    protected $casts = [
        'clickable_areas' => 'array',
        'page_number' => 'integer',
    ];

    /**
     * Get the machine that owns this drawing
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * Scope to filter by drawing type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('drawing_type', $type);
    }

    /**
     * Get the clickable areas as components
     */
    public function getClickableComponentsAttribute()
    {
        if (!$this->clickable_areas) {
            return collect();
        }

        $componentIds = collect($this->clickable_areas)->pluck('component_id')->unique()->filter();
        
        return Component::whereIn('id', $componentIds)->get();
    }

    /**
     * Find component at specific coordinates
     */
    public function findComponentAtPosition($x, $y)
    {
        if (!$this->clickable_areas) {
            return null;
        }

        foreach ($this->clickable_areas as $area) {
            if (
                $x >= $area['x'] && 
                $x <= ($area['x'] + $area['width']) &&
                $y >= $area['y'] && 
                $y <= ($area['y'] + $area['height'])
            ) {
                return Component::find($area['component_id']);
            }
        }

        return null;
    }
}