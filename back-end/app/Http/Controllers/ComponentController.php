<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Http\Requests\StoreComponentRequest;
use App\Http\Requests\UpdateComponentRequest; // Ensure this class exists in the specified namespace
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\SearchHistory;

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
            $query->spareParts();
        }
        
        // Filter wearing parts
        if ($request->boolean('wearing_parts_only')) {
            $query->wearingParts();
        }
        
        // Search functionality
        if ($request->has('search')) {
            $query->search($request->search);
            
            // Record search history
            SearchHistory::recordSearch(
                $request->search,
                'component',
                $query->count(),
                $request->user()->id ?? null
            );
        }
        
        $components = $query->with([
                            'machine',
                            'category',
                            'primaryImage',
                            'specifications'
                        ])
                       ->paginate($request->get('per_page', 20));
        
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
        if ($request->has('specifications')) {
            foreach ($request->specifications as $spec) {
                $component->specifications()->create($spec);
            }
        }
        
        // Handle image uploads if provided
        if ($request->hasFile('images')) {
            $this->handleImageUploads($component, $request->file('images'));
        }
        
        // Load relationships for the response
        $component->load(['machine', 'category', 'primaryImage', 'specifications']);
        
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
        // Load all relationships
        $component->load([
            'machine',
            'category',
            'images' => function ($query) {
                $query->ordered();
            },
            'specifications',
            'favorites'
        ]);
        
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
        if ($request->has('specifications')) {
            // Delete existing specifications
            $component->specifications()->delete();
            
            // Create new specifications
            foreach ($request->specifications as $spec) {
                $component->specifications()->create($spec);
            }
        }
        
        // Handle new image uploads
        if ($request->hasFile('images')) {
            $this->handleImageUploads($component, $request->file('images'), true);
        }
        
        // Load relationships for the response
        $component->load(['machine', 'category', 'primaryImage', 'specifications']);
        
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
        foreach ($component->images as $image) {
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
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
        $query = $request->validate([
            'search' => 'required|string|min:2',
            'machine_id' => 'nullable|exists:machines,id',
            'category_id' => 'nullable|exists:categories,id',
            'spare_parts_only' => 'boolean',
            'wearing_parts_only' => 'boolean',
            'per_page' => 'integer|min:5|max:100'
        ]);
        
        $builder = Component::search($query['search']);
        
        // Apply filters
        if (isset($query['machine_id'])) {
            $builder->where('machine_id', $query['machine_id']);
        }
        
        if (isset($query['category_id'])) {
            $builder->where('category_id', $query['category_id']);
        }
        
        if ($query['spare_parts_only'] ?? false) {
            $builder->spareParts();
        }
        
        if ($query['wearing_parts_only'] ?? false) {
            $builder->wearingParts();
        }
        
        $components = $builder->with(['machine', 'category', 'primaryImage'])
                             ->paginate($query['per_page'] ?? 20);
        
        // Record search history
        SearchHistory::recordSearch(
            $query['search'],
            'component',
            $components->total(),
            $request->user()->id ?? null
        );
        
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
        
        $component = Component::where('machine_id', $request->machine_id)
                            ->where('pos_number', $request->pos_number)
                            ->with(['category', 'primaryImage', 'specifications'])
                            ->first();
        
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
        if ($deleteExisting) {
            // Delete existing images
            foreach ($component->images as $image) {
                Storage::disk('public')->delete($image->image_path);
            }
            $component->images()->delete();
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