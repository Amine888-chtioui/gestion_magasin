<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Component extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'category_id',
        'pos_number',
        'quantity',
        'unit',
        'name_de',
        'name_en',
        'sap_number',
        'description',
        'is_spare_part',
        'is_wearing_part',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'is_spare_part' => 'boolean',
        'is_wearing_part' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the machine that owns this component
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /**
     * Get the category of this component
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all specifications for this component
     */
    public function specifications(): HasMany
    {
        return $this->hasMany(ComponentSpecification::class);
    }

    /**
     * Get all images for this component
     */
    public function images(): HasMany
    {
        return $this->hasMany(ComponentImage::class);
    }

    /**
     * Get the primary image for this component
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ComponentImage::class)->where('is_primary', true);
    }

    /**
     * Get all favorites for this component
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class)
                    ->where('favorite_type', 'component');
    }

    /**
     * Scope to search components by name, SAP number, or position
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name_de', 'like', "%{$search}%")
              ->orWhere('name_en', 'like', "%{$search}%")
              ->orWhere('sap_number', 'like', "%{$search}%")
              ->orWhere('pos_number', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to filter spare parts
     */
    public function scopeSpareParts($query)
    {
        return $query->where('is_spare_part', true);
    }

    /**
     * Scope to filter wearing parts
     */
    public function scopeWearingParts($query)
    {
        return $query->where('is_wearing_part', true);
    }

    /**
     * Get the display name based on user's preference or language
     */
    public function getDisplayNameAttribute($lang = 'en')
    {
        if ($lang === 'de' && $this->name_de) {
            return $this->name_de;
        }
        
        return $this->name_en ?? $this->name_de;
    }
}