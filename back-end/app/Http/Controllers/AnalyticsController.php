<?php

namespace App\Http\Controllers;

use App\Models\SearchHistory;
use App\Models\Machine;
use App\Models\Component;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get search trends
     */
    public function searchTrends(Request $request)
    {
        $request->validate([
            'period' => 'nullable|in:today,week,month,year',
            'type' => 'nullable|in:all,machine,component'
        ]);
        
        $period = $request->input('period', 'week');
        $type = $request->input('type', 'all');
        
        // Define date range based on period
        $dateRange = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subWeek()
        };
        
        $query = SearchHistory::where('searched_at', '>=', $dateRange);
        
        if ($type !== 'all') {
            $query->where('search_type', $type);
        }
        
        $trends = $query->select(
                            'search_query',
                            DB::raw('COUNT(*) as count'),
                            DB::raw('DATE(searched_at) as date')
                        )
                        ->groupBy('search_query', 'date')
                        ->orderByDesc('count')
                        ->limit(50)
                        ->get();
        
        return response()->json([
            'period' => $period,
            'type' => $type,
            'trends' => $trends
        ]);
    }

    /**
     * Get popular machines
     */
    public function popularMachines(Request $request)
    {
        $request->validate([
            'period' => 'nullable|in:today,week,month,year',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);
        
        $period = $request->input('period', 'month');
        $limit = $request->input('limit', 20);
        
        // Date range
        $dateRange = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth()
        };
        
        // Get machines based on favorites and searches
        $popularMachines = Machine::withCount([
            'favorites as favorites_count' => function ($query) use ($dateRange) {
                $query->where('created_at', '>=', $dateRange);
            }
        ])
        ->select('*')
        ->selectRaw('(SELECT COUNT(*) FROM search_histories WHERE search_type = "machine" AND searched_at >= ? AND machine_id = machines.id) as search_count', [$dateRange])
        ->orderByDesc('favorites_count')
        ->orderByDesc('search_count')
        ->limit($limit)
        ->get();
        
        return response()->json([
            'period' => $period,
            'machines' => $popularMachines
        ]);
    }

    /**
     * Get popular components
     */
    public function popularComponents(Request $request)
    {
        $request->validate([
            'period' => 'nullable|in:today,week,month,year',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);
        
        $period = $request->input('period', 'month');
        $limit = $request->input('limit', 20);
        
        // Date range
        $dateRange = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth()
        };
        
        // Get components based on favorites and searches
        $popularComponents = Component::withCount([
            'favorites as favorites_count' => function ($query) use ($dateRange) {
                $query->where('created_at', '>=', $dateRange);
            }
        ])
        ->with(['machine', 'category', 'primaryImage'])
        ->select('*')
        ->selectRaw('(SELECT COUNT(*) FROM search_histories WHERE search_type = "component" AND searched_at >= ? AND component_id = components.id) as search_count', [$dateRange])
        ->orderByDesc('favorites_count')
        ->orderByDesc('search_count')
        ->limit($limit)
        ->get();
        
        return response()->json([
            'period' => $period,
            'components' => $popularComponents
        ]);
    }

    /**
     * Get user favorites analytics
     */
    public function userFavorites(Request $request)
    {
        $userId = $request->user()->id ?? $request->header('X-Session-ID');
        
        $analytics = [
            'total_favorites' => Favorite::forUser($userId)->count(),
            'machine_favorites' => Favorite::forUser($userId)->machines()->count(),
            'component_favorites' => Favorite::forUser($userId)->components()->count(),
            'recent_favorites' => Favorite::forUser($userId)
                                         ->with(['machine', 'component.machine', 'component.category'])
                                         ->latest()
                                         ->limit(10)
                                         ->get(),
            'favorite_machines_analysis' => $this->getFavoriteMachinesAnalytics($userId),
            'favorite_categories' => $this->getFavoriteCategoriesAnalytics($userId)
        ];
        
        return response()->json($analytics);
    }

    /**
     * Get analytics for user's favorite machines
     */
    private function getFavoriteMachinesAnalytics($userId)
    {
        return Machine::whereHas('favorites', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->withCount([
                    'components',
                    'components as spare_parts_count' => function ($query) {
                        $query->where('is_spare_part', true);
                    },
                    'components as wearing_parts_count' => function ($query) {
                        $query->where('is_wearing_part', true);
                    }
                ])
                ->get();
    }

    /**
     * Get analytics for user's favorite component categories
     */
    private function getFavoriteCategoriesAnalytics($userId)
    {
        return DB::table('categories')
                 ->select('categories.*', DB::raw('COUNT(favorites.id) as favorites_count'))
                 ->leftJoin('components', 'categories.id', '=', 'components.category_id')
                 ->leftJoin('favorites', function ($join) use ($userId) {
                     $join->on('components.id', '=', 'favorites.component_id')
                          ->where('favorites.user_id', $userId)
                          ->where('favorites.favorite_type', 'component');
                 })
                 ->groupBy('categories.id')
                 ->having('favorites_count', '>', 0)
                 ->orderByDesc('favorites_count')
                 ->get();
    }
}