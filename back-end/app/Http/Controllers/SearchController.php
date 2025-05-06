<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\Component;
use App\Models\SearchHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Global search across machines and components
     */
    public function index(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'type' => 'nullable|in:all,machines,components',
            'per_page' => 'integer|min:5|max:100'
        ]);
        
        $query = $request->input('q');
        $type = $request->input('type', 'all');
        $perPage = $request->input('per_page', 20);
        
        $results = [];
        
        if ($type === 'all' || $type === 'machines') {
            $machines = Machine::search($query)
                              ->with(['drawings'])
                              ->paginate($perPage);
            
            $results['machines'] = $machines;
        }
        
        if ($type === 'all' || $type === 'components') {
            $components = Component::search($query)
                                  ->with(['machine', 'category', 'primaryImage'])
                                  ->paginate($perPage);
            
            $results['components'] = $components;
        }
        
        // Record search history
        $totalResults = collect($results)->sum(function ($result) {
            return $result->total();
        });
        
        SearchHistory::recordSearch(
            $query,
            $type,
            $totalResults,
            $request->user()->id ?? null
        );
        
        return response()->json($results);
    }

    /**
     * Get search suggestions
     */
    public function suggestions(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1',
            'limit' => 'integer|min:1|max:20'
        ]);
        
        $query = $request->input('q');
        $limit = $request->input('limit', 10);
        
        // Get suggestions from machines
        $machineSuggestions = Machine::search($query)
                                   ->select('id', 'name', 'model', 'sap_number')
                                   ->limit($limit)
                                   ->get()
                                   ->map(function ($machine) {
                                       return [
                                           'type' => 'machine',
                                           'id' => $machine->id,
                                           'text' => $machine->name,
                                           'subtitle' => "Model: {$machine->model}",
                                           'sap' => $machine->sap_number
                                       ];
                                   });
        
        // Get suggestions from components
        $componentSuggestions = Component::search($query)
                                       ->select('id', 'name_de', 'name_en', 'pos_number', 'sap_number', 'machine_id')
                                       ->with('machine:id,name')
                                       ->limit($limit)
                                       ->get()
                                       ->map(function ($component) {
                                           return [
                                               'type' => 'component',
                                               'id' => $component->id,
                                               'text' => $component->name_en ?? $component->name_de,
                                               'subtitle' => "Pos: {$component->pos_number} | Machine: {$component->machine->name}",
                                               'sap' => $component->sap_number
                                           ];
                                       });
        
        // Get popular queries from history
        $popularQueries = SearchHistory::where('search_query', 'like', "{$query}%")
                                      ->select('search_query', DB::raw('COUNT(*) as count'))
                                      ->groupBy('search_query')
                                      ->orderByDesc('count')
                                      ->limit($limit)
                                      ->get()
                                      ->map(function ($history) {
                                          return [
                                              'type' => 'query',
                                              'text' => $history->search_query,
                                              'subtitle' => "{$history->count} searches"
                                          ];
                                      });
        
        return response()->json([
            'suggestions' => [
                'machines' => $machineSuggestions,
                'components' => $componentSuggestions,
                'popular' => $popularQueries
            ]
        ]);
    }

    /**
     * Get search history for a user
     */
    public function history(Request $request)
    {
        $request->validate([
            'limit' => 'integer|min:1|max:50',
            'type' => 'nullable|in:all,machine,component'
        ]);
        
        $userId = $request->user()->id ?? $request->header('X-Session-ID');
        $limit = $request->input('limit', 20);
        $type = $request->input('type');
        
        $query = SearchHistory::forUser($userId);
        
        if ($type) {
            $query->byType($type);
        }
        
        $history = $query->recent($limit)->get();
        
        return response()->json($history);
    }

    /**
     * Get popular search queries
     */
    public function popular(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:all,machine,component',
            'limit' => 'integer|min:1|max:50'
        ]);
        
        $type = $request->input('type');
        $limit = $request->input('limit', 20);
        
        $popularQueries = SearchHistory::getPopularQueries($type, $limit);
        
        return response()->json($popularQueries);
    }

    /**
     * Advanced search with filters
     */
    public function advanced(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'filters' => 'required|array',
            'filters.machines' => 'nullable|array',
            'filters.categories' => 'nullable|array',
            'filters.spare_parts' => 'nullable|boolean',
            'filters.wearing_parts' => 'nullable|boolean',
            'filters.has_specifications' => 'nullable|boolean',
            'filters.has_images' => 'nullable|boolean',
            'per_page' => 'integer|min:5|max:100'
        ]);
        
        $query = $request->input('q');
        $filters = $request->input('filters');
        $perPage = $request->input('per_page', 20);
        
        $componentQuery = Component::search($query);
        
        // Apply filters
        if (!empty($filters['machines'])) {
            $componentQuery->whereIn('machine_id', $filters['machines']);
        }
        
        if (!empty($filters['categories'])) {
            $componentQuery->whereIn('category_id', $filters['categories']);
        }
        
        if ($filters['spare_parts'] ?? false) {
            $componentQuery->where('is_spare_part', true);
        }
        
        if ($filters['wearing_parts'] ?? false) {
            $componentQuery->where('is_wearing_part', true);
        }
        
        if ($filters['has_specifications'] ?? false) {
            $componentQuery->has('specifications');
        }
        
        if ($filters['has_images'] ?? false) {
            $componentQuery->has('images');
        }
        
        $results = $componentQuery->with(['machine', 'category', 'primaryImage', 'specifications'])
                                 ->paginate($perPage);
        
        // Record search
        SearchHistory::recordSearch(
            $query,
            'advanced',
            $results->total(),
            $request->user()->id ?? null
        );
        
        return response()->json($results);
    }
}