<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\SearchHistory;
use App\Http\Requests\StoreComponentRequest;
use App\Http\Requests\UpdateComponentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ComponentController extends Controller
{
    /**
     * Display a listing of components
     */
    public function index(Request $request)
    {
        $query = Component::query();
        
        // Filter by machine
        if ($request->has('machine_id')) {
            $query->where('machine_id', $request->machine_id);
        }
        
        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // Filter spare parts
        if ($request->boolean('spare_parts_only')) {
            $query->where('is_spare_part', true);
        }
        
        // Filter wearing parts
        if ($request->boolean('wearing_parts_only')) {
            $query->where('is_wearing_part', true);
        }
        
        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name_de', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%")
                  ->orWhere('sap_number', 'like', "%{$search}%")
                  ->orWhere('pos_number', 'like', "%{$search}%");
            });
            
            // Record search history if the model exists
            if (class_exists('App\\Models\\SearchHistory')) {
                $userId = $request->user()->id ?? $request->header('X-Session-ID', null);
                SearchHistory::create([
                    'search_query' => $search,
                    'search_type' => 'component',
                    'results_count' => $query->count(),
                    'searched_at' => now(),
                    'user_id' => $userId,
                ]);
            }
        }
        
        // Load relationships
        $query->with([
            'machine:id,name,model',
            'category:id,name',
        ]);
        
        // Check if each relationship exists before attempting to load
        if (method_exists(Component::class, 'primaryImage')) {
            $query->with('primaryImage');
        }
        
        if (method_exists(Component::class, 'specifications')) {
            $query->with('specifications');
        }
        
        // Get pagination settings
        $perPage = $request->get('per_page', 20);
        
        // Execute query
        $components = $query->paginate($perPage);
        
        return response()->json($components);
    }

    /**
     * Store a newly created component
     */
    public function store(StoreComponentRequest $request)
    {
        $validated = $request->validated();
        
        $component = Component::create($validated);
        
        // Handle specifications if provided
        if ($request->has('specifications') && method_exists(Component::class, 'specifications')) {
            foreach ($request->specifications as $spec) {
                $component->specifications()->create($spec);
            }
        }
        
        // Handle image uploads if provided
        if ($request->hasFile('images') && method_exists($this, 'handleImageUploads')) {
            $this->handleImageUploads($component, $request->file('images'));
        }
        
        // Load relationships for the response
        $component->load(['machine:id,name,model', 'category:id,name']);
        
        if (method_exists(Component::class, 'primaryImage')) {
            $component->load('primaryImage');
        }
        
        if (method_exists(Component::class, 'specifications')) {
            $component->load('specifications');
        }
        
        return response()->json([
            'message' => 'Component created successfully',
            'component' => $component
        ], 201);
    }

    /**
     * Display the specified component
     */
    public function show(Component $component)
    {
        // Basic relationships
        $component->load(['machine:id,name,model', 'category:id,name']);
        
        // Optional relationships
        if (method_exists(Component::class, 'images')) {
            $component->load('images');
        }
        
        if (method_exists(Component::class, 'specifications')) {
            $component->load('specifications');
        }
        
        if (method_exists(Component::class, 'favorites')) {
            $component->load('favorites');
        }
        
        return response()->json($component);
    }

    /**
     * Update the specified component
     */
    public function update(UpdateComponentRequest $request, Component $component)
    {
        $validated = $request->validated();
        
        $component->update($validated);
        
        // Update specifications if provided
        if ($request->has('specifications') && method_exists(Component::class, 'specifications')) {
            // Delete existing specifications
            $component->specifications()->delete();
            
            // Create new specifications
            foreach ($request->specifications as $spec) {
                $component->specifications()->create($spec);
            }
        }
        
        // Handle new image uploads
        if ($request->hasFile('images') && method_exists($this, 'handleImageUploads')) {
            $this->handleImageUploads($component, $request->file('images'), true);
        }
        
        // Load relationships for the response
        $component->load(['machine:id,name,model', 'category:id,name']);
        
        if (method_exists(Component::class, 'primaryImage')) {
            $component->load('primaryImage');
        }
        
        if (method_exists(Component::class, 'specifications')) {
            $component->load('specifications');
        }
        
        return response()->json([
            'message' => 'Component updated successfully',
            'component' => $component
        ]);
    }

    /**
     * Remove the specified component
     */
    public function destroy(Component $component)
    {
        // Delete associated images
        if (method_exists(Component::class, 'images')) {
            foreach ($component->images as $image) {
                Storage::disk('public')->delete($image->image_path);
                $image->delete();
            }
        }
        
        // Delete component (specifications will be deleted due to cascade)
        $component->delete();
        
        return response()->json([
            'message' => 'Component deleted successfully'
        ]);
    }

    /**
     * Search components across all machines
     */
    public function search(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2',
            'machine_id' => 'nullable|exists:machines,id',
            'category_id' => 'nullable|exists:categories,id',
            'spare_parts_only' => 'boolean',
            'wearing_parts_only' => 'boolean',
            'per_page' => 'integer|min:5|max:100'
        ]);
        
        $search = $request->search;
        $query = Component::query();
        
        // Apply search
        $query->where(function($q) use ($search) {
            $q->where('name_de', 'like', "%{$search}%")
              ->orWhere('name_en', 'like', "%{$search}%")
              ->orWhere('sap_number', 'like', "%{$search}%")
              ->orWhere('pos_number', 'like', "%{$search}%");
        });
        
        // Apply filters
        if ($request->has('machine_id')) {
            $query->where('machine_id', $request->machine_id);
        }
        
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->boolean('spare_parts_only', false)) {
            $query->where('is_spare_part', true);
        }
        
        if ($request->boolean('wearing_parts_only', false)) {
            $query->where('is_wearing_part', true);
        }
        
        // Load relationships
        $query->with(['machine:id,name,model', 'category:id,name']);
        
        if (method_exists(Component::class, 'primaryImage')) {
            $query->with('primaryImage');
        }
        
        // Get pagination
        $perPage = $request->get('per_page', 20);
        $components = $query->paginate($perPage);
        
        // Record search history if model exists
        if (class_exists('App\\Models\\SearchHistory')) {
            $userId = $request->user()->id ?? $request->header('X-Session-ID', null);
            SearchHistory::create([
                'search_query' => $search,
                'search_type' => 'component',
                'results_count' => $components->total(),
                'searched_at' => now(),
                'user_id' => $userId,
            ]);
        }
        
        return response()->json($components);
    }

    /**
     * Get components by position number (for interactive drawings)
     */
    public function findByPosition(Request $request)
    {
        $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'pos_number' => 'required|string'
        ]);
        
        $query = Component::where('machine_id', $request->machine_id)
                          ->where('pos_number', $request->pos_number);
        
        // Load relationships
        if (method_exists(Component::class, 'category')) {
            $query->with('category:id,name');
        }
        
        if (method_exists(Component::class, 'primaryImage')) {
            $query->with('primaryImage');
        }
        
        if (method_exists(Component::class, 'specifications')) {
            $query->with('specifications');
        }
        
        $component = $query->first();
        
        if (!$component) {
            return response()->json([
                'error' => 'Component not found'
            ], 404);
        }
        
        return response()->json($component);
    }

    /**
     * Handle image uploads for a component
     */
    private function handleImageUploads(Component $component, $images, $deleteExisting = false)
    {
        if ($deleteExisting && method_exists(Component::class, 'images')) {
            // Delete existing images
            foreach ($component->images as $image) {
                Storage::disk('public')->delete($image->image_path);
            }
            $component->images()->delete();
        }
        
        if (!method_exists(Component::class, 'images')) {
            return;
        }
        
        foreach ($images as $index => $image) {
            $path = $image->store('components', 'public');
            
            $component->images()->create([
                'image_path' => $path,
                'alt_text' => $component->name_en ?? $component->name_de,
                'is_primary' => $index === 0, // First image is primary
                'sort_order' => $index
            ]);
        }
    }
}