<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Machine;
use App\Models\Component;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Get all favorites for the current user
     */
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:all,machine,component'
        ]);
        
        $userId = $request->user()->id ?? $request->header('X-Session-ID');
        $type = $request->input('type', 'all');
        
        $query = Favorite::forUser($userId);
        
        if ($type === 'machine') {
            $query->machines()->with('machine');
        } elseif ($type === 'component') {
            $query->components()->with(['component.machine', 'component.category', 'component.primaryImage']);
        } else {
            $query->with([
                'machine',
                'component.machine',
                'component.category',
                'component.primaryImage'
            ]);
        }
        
        $favorites = $query->latest()->get();
        
        // Group favorites by type for easier frontend handling
        $grouped = $favorites->groupBy('favorite_type')->map(function ($items) {
            return $items->map(function ($favorite) {
                return [
                    'id' => $favorite->id,
                    'item' => $favorite->favorited_item,
                    'created_at' => $favorite->created_at
                ];
            });
        });
        
        return response()->json($grouped);
    }

    /**
     * Add a machine to favorites
     */
    public function addMachine(Request $request, Machine $machine)
    {
        $userId = $request->user()->id ?? $request->header('X-Session-ID');
        
        $favorite = Favorite::firstOrCreate([
            'user_id' => $userId,
            'machine_id' => $machine->id,
            'favorite_type' => 'machine'
        ]);
        
        return response()->json([
            'message' => $favorite->wasRecentlyCreated ? 'Machine added to favorites' : 'Machine already in favorites',
            'favorite' => $favorite->load('machine')
        ], $favorite->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Add a component to favorites
     */
    public function addComponent(Request $request, Component $component)
    {
        $userId = $request->user()->id ?? $request->header('X-Session-ID');
        
        $favorite = Favorite::firstOrCreate([
            'user_id' => $userId,
            'component_id' => $component->id,
            'favorite_type' => 'component'
        ]);
        
        return response()->json([
            'message' => $favorite->wasRecentlyCreated ? 'Component added to favorites' : 'Component already in favorites',
            'favorite' => $favorite->load(['component.machine', 'component.category', 'component.primaryImage'])
        ], $favorite->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Remove a machine from favorites
     */
    public function removeMachine(Request $request, Machine $machine)
    {
        $userId = $request->user()->id ?? $request->header('X-Session-ID');
        
        $deleted = Favorite::where('user_id', $userId)
                          ->where('machine_id', $machine->id)
                          ->where('favorite_type', 'machine')
                          ->delete();
        
        if ($deleted) {
            return response()->json(['message' => 'Machine removed from favorites']);
        }
        
        return response()->json(['message' => 'Machine not found in favorites'], 404);
    }

    /**
     * Remove a component from favorites
     */
    public function removeComponent(Request $request, Component $component)
    {
        $userId = $request->user()->id ?? $request->header('X-Session-ID');
        
        $deleted = Favorite::where('user_id', $userId)
                          ->where('component_id', $component->id)
                          ->where('favorite_type', 'component')
                          ->delete();
        
        if ($deleted) {
            return response()->json(['message' => 'Component removed from favorites']);
        }
        
        return response()->json(['message' => 'Component not found in favorites'], 404);
    }

    /**
     * Toggle favorite status for a machine
     */
    public function toggleMachine(Request $request, Machine $machine)
    {
        $userId = $request->user()->id ?? $request->header('X-Session-ID');
        
        $favorite = Favorite::where('user_id', $userId)
                            ->where('machine_id', $machine->id)
                            ->where('favorite_type', 'machine')
                            ->first();
        
        if ($favorite) {
            $favorite->delete();
            return response()->json([
                'message' => 'Machine removed from favorites',
                'is_favorite' => false
            ]);
        } else {
            $favorite = Favorite::create([
                'user_id' => $userId,
                'machine_id' => $machine->id,
                'favorite_type' => 'machine'
            ]);
            
            return response()->json([
                'message' => 'Machine added to favorites',
                'is_favorite' => true,
                'favorite' => $favorite->load('machine')
            ]);
        }
    }

    /**
     * Toggle favorite status for a component
     */
    public function toggleComponent(Request $request, Component $component)
    {
        $userId = $request->user()->id ?? $request->header('X-Session-ID');
        
        $favorite = Favorite::where('user_id', $userId)
                            ->where('component_id', $component->id)
                            ->where('favorite_type', 'component')
                            ->first();
        
        if ($favorite) {
            $favorite->delete();
            return response()->json([
                'message' => 'Component removed from favorites',
                'is_favorite' => false
            ]);
        } else {
            $favorite = Favorite::create([
                'user_id' => $userId,
                'component_id' => $component->id,
                'favorite_type' => 'component'
            ]);
            
            return response()->json([
                'message' => 'Component added to favorites',
                'is_favorite' => true,
                'favorite' => $favorite->load(['component.machine', 'component.category', 'component.primaryImage'])
            ]);
        }
    }

    /**
     * Check if item is favorited
     */
    public function checkStatus(Request $request)
    {
        $request->validate([
            'type' => 'required|in:machine,component',
            'id' => 'required|integer'
        ]);
        
        $userId = $request->user()->id ?? $request->header('X-Session-ID');
        $type = $request->input('type');
        $id = $request->input('id');
        
        $exists = false;
        
        if ($type === 'machine') {
            $exists = Favorite::where('user_id', $userId)
                             ->where('machine_id', $id)
                             ->where('favorite_type', 'machine')
                             ->exists();
        } else {
            $exists = Favorite::where('user_id', $userId)
                             ->where('component_id', $id)
                             ->where('favorite_type', 'component')
                             ->exists();
        }
        
        return response()->json(['is_favorite' => $exists]);
    }

    /**
     * Get user's favorite machines with their components
     */
    public function getMachinesWithComponents(Request $request)
    {
        $userId = $request->user()->id ?? $request->header('X-Session-ID');
        
        $favoriteMachines = Favorite::forUser($userId)
                                   ->machines()
                                   ->with('machine.components.primaryImage')
                                   ->get()
                                   ->map(function ($favorite) {
                                       $machine = $favorite->machine;
                                       $machine->favorite_components = Favorite::forUser($favorite->user_id)
                                                                              ->components()
                                                                              ->whereHas('component', function ($query) use ($machine) {
                                                                                  $query->where('machine_id', $machine->id);
                                                                              })
                                                                              ->with(['component.primaryImage', 'component.category'])
                                                                              ->get()
                                                                              ->pluck('component');
                                       return $machine;
                                   });
        
        return response()->json($favoriteMachines);
    }
}