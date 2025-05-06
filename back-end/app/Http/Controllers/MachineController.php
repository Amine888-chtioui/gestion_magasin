<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Http\Requests\StoreMachineRequest;
use App\Http\Requests\UpdateMachineRequest; // Ensure this class exists in the specified namespace
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MachineController extends Controller
{
    /**
     * Display a listing of machines
     */
    public function index(Request $request)
    {
        $query = Machine::query();
        
        // Search functionality
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        // Pagination
        $machines = $query->with(['drawings', 'components'])
                         ->paginate($request->get('per_page', 15));
        
        return response()->json($machines);
    }

    /**
     * Store a newly created machine
     */
    public function store(StoreMachineRequest $request)
    {
        $validated = $request->validated();
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('machines', 'public');
        }
        
        $machine = Machine::create($validated);
        
        // Load relationships for the response
        $machine->load(['drawings', 'components']);
        
        return response()->json([
            'message' => 'Machine created successfully',
            'machine' => $machine
        ], 201);
    }

    /**
     * Display the specified machine
     */
    public function show(Machine $machine)
    {
        // Load all relationships
        $machine->load([
            'components' => function ($query) {
                $query->with(['category', 'primaryImage', 'specifications']);
            },
            'drawings',
            'favorites'
        ]);
        
        return response()->json($machine);
    }

    /**
     * Update the specified machine
     */
    public function update(UpdateMachineRequest $request, Machine $machine)
    {
        $validated = $request->validated();
        
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($machine->image_path) {
                Storage::disk('public')->delete($machine->image_path);
            }
            
            $validated['image_path'] = $request->file('image')->store('machines', 'public');
        }
        
        $machine->update($validated);
        
        // Load relationships for the response
        $machine->load(['drawings', 'components']);
        
        return response()->json([
            'message' => 'Machine updated successfully',
            'machine' => $machine
        ]);
    }

    /**
     * Remove the specified machine
     */
    public function destroy(Machine $machine)
    {
        // Delete associated image
        if ($machine->image_path) {
            Storage::disk('public')->delete($machine->image_path);
        }
        
        // Delete the machine (components and drawings will be deleted due to cascade)
        $machine->delete();
        
        return response()->json([
            'message' => 'Machine deleted successfully'
        ]);
    }

    /**
     * Get components for a specific machine
     */
    public function components(Machine $machine, Request $request)
    {
        $query = $machine->components();
        
        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // Filter spare parts only
        if ($request->boolean('spare_parts_only')) {
            $query->spareParts();
        }
        
        // Filter wearing parts only
        if ($request->boolean('wearing_parts_only')) {
            $query->wearingParts();
        }
        
        // Search within components
        if ($request->has('search')) {
            $query->search($request->search);
        }
        
        $components = $query->with(['category', 'primaryImage', 'specifications'])
                          ->paginate($request->get('per_page', 20));
        
        return response()->json($components);
    }

    /**
     * Get interactive drawing for a machine
     */
    public function drawing(Machine $machine, Request $request)
    {
        $drawing = $machine->drawings()
                          ->ofType($request->get('type', 'exploded'))
                          ->first();
        
        if (!$drawing) {
            return response()->json([
                'error' => 'Drawing not found'
            ], 404);
        }
        
        // Load related components
        $drawing->load('clickableComponents');
        
        return response()->json($drawing);
    }
}