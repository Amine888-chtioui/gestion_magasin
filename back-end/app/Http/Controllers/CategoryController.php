<?php
// File: app/Http/Controllers/CategoryController.php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index(Request $request)
    {
        $query = Category::query();
        
        // Search functionality
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        // Get components count if requested
        if ($request->boolean('with_count')) {
            $query->withCount('components');
        }
        
        $categories = $query->orderBy('name')
                           ->paginate($request->get('per_page', 20));
        
        return response()->json($categories);
    }

    /**
     * Store a newly created category
     */
    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('categories', 'public');
        }
        
        $category = Category::create($validated);
        
        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category
        ], 201);
    }

    /**
     * Display the specified category
     */
    public function show(Category $category)
    {
        // Load components count and recent components
        $category->loadCount('components');
        $category->load(['components' => function ($query) {
            $query->with(['machine', 'primaryImage'])->latest()->limit(10);
        }]);
        
        return response()->json($category);
    }

    /**
     * Update the specified category
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $validated = $request->validated();
        
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            
            $validated['image_path'] = $request->file('image')->store('categories', 'public');
        }
        
        $category->update($validated);
        
        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }

    /**
     * Remove the specified category
     */
    public function destroy(Category $category)
    {
        // Check if category has components
        if ($category->components()->exists()) {
            return response()->json([
                'error' => 'Cannot delete category that has components'
            ], 400);
        }
        
        // Delete associated image
        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }
        
        $category->delete();
        
        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }

    /**
     * Get components for a specific category
     */
    public function components(Category $category, Request $request)
    {
        $query = $category->components();
        
        // Search within category
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        // Filter by machine
        if ($request->has('machine_id')) {
            $query->where('machine_id', $request->machine_id);
        }
        
        // Filter spare parts
        if ($request->boolean('spare_parts_only')) {
            $query->spareParts();
        }
        
        // Filter wearing parts
        if ($request->boolean('wearing_parts_only')) {
            $query->wearingParts();
        }
        
        $components = $query->with(['machine', 'primaryImage', 'specifications'])
                           ->paginate($request->get('per_page', 20));
        
        return response()->json($components);
    }
}