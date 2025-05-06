<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model
{
    use HasFactory;

    // Disable timestamps as we have a custom 'searched_at' field
    public $timestamps = false;

    protected $fillable = [
        'search_query',
        'search_type',
        'results_count',
        'searched_at',
        'user_id',
    ];

    protected $casts = [
        'results_count' => 'integer',
        'searched_at' => 'datetime',
    ];

    /**
     * Scope to get recent searches
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('searched_at', 'desc')->limit($limit);
    }

    /**
     * Scope to get searches by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get searches by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('search_type', $type);
    }

    /**
     * Get popular search queries
     */
    public static function getPopularQueries($type = null, $limit = 10)
    {
        $query = static::selectRaw('search_query, COUNT(*) as count')
            ->groupBy('search_query')
            ->orderByDesc('count');

        if ($type) {
            $query->where('search_type', $type);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Record a search
     */
    public static function recordSearch($query, $type, $resultsCount, $userId = null)
    {
        return static::create([
            'search_query' => $query,
            'search_type' => $type,
            'results_count' => $resultsCount,
            'searched_at' => now(),
            'user_id' => $userId,
        ]);
    }
}