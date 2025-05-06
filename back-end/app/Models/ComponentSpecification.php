<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentSpecification extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_id',
        'spec_key',
        'spec_value',
        'spec_unit',
    ];

    /**
     * Get the component that owns this specification
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * Get the formatted specification value with unit
     */
    public function getFormattedValueAttribute()
    {
        if ($this->spec_unit) {
            return "{$this->spec_value} {$this->spec_unit}";
        }
        
        return $this->spec_value;
    }

    /**
     * Scope to find specifications by key
     */
    public function scopeByKey($query, $key)
    {
        return $query->where('spec_key', $key);
    }
}