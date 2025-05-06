<?php

namespace App\Http\Controllers;

use App\Models\MachineDrawing;
use App\Http\Requests\DrawingUploadRequest;
use App\Http\Requests\UpdateDrawingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DrawingController extends Controller
{
    /**
     * Display the specified drawing
     */
    public function show(MachineDrawing $drawing)
    {
        $drawing->load(['machine', 'clickableComponents']);
        
        return response()->json($drawing);
    }

    /**
     * Store a newly created drawing
     */
    public function store(DrawingUploadRequest $request)
    {
        $validated = $request->validated();
        
        // Handle file upload
        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store('drawings', 'public');
        }
        
        $drawing = MachineDrawing::create($validated);
        
        return response()->json([
            'message' => 'Drawing uploaded successfully',
            'drawing' => $drawing->load('machine')
        ], 201);
    }

    /**
     * Update the specified drawing
     */
    public function update(UpdateDrawingRequest $request, MachineDrawing $drawing)
    {
        $validated = $request->validated();
        
        // Handle file replacement if provided
        if ($request->hasFile('file')) {
            // Delete old file
            if ($drawing->file_path) {
                Storage::disk('public')->delete($drawing->file_path);
            }
            
            $validated['file_path'] = $request->file('file')->store('drawings', 'public');
        }
        
        $drawing->update($validated);
        
        return response()->json([
            'message' => 'Drawing updated successfully',
            'drawing' => $drawing->load('machine')
        ]);
    }

    /**
     * Remove the specified drawing
     */
    public function destroy(MachineDrawing $drawing)
    {
        // Delete the file
        if ($drawing->file_path) {
            Storage::disk('public')->delete($drawing->file_path);
        }
        
        $drawing->delete();
        
        return response()->json([
            'message' => 'Drawing deleted successfully'
        ]);
    }

    /**
     * Get clickable areas for a drawing
     */
    public function getClickableAreas(MachineDrawing $drawing)
    {
        return response()->json([
            'clickable_areas' => $drawing->clickable_areas,
            'components' => $drawing->clickableComponents
        ]);
    }

    /**
     * Find component at specific position
     */
    public function findComponentAtPosition(Request $request, MachineDrawing $drawing)
    {
        $request->validate([
            'x' => 'required|numeric|min:0',
            'y' => 'required|numeric|min:0'
        ]);
        
        $component = $drawing->findComponentAtPosition(
            $request->input('x'),
            $request->input('y')
        );
        
        if (!$component) {
            return response()->json([
                'error' => 'No component found at this position'
            ], 404);
        }
        
        return response()->json($component->load(['category', 'primaryImage', 'specifications']));
    }

    /**
     * Update clickable areas for a drawing
     */
    public function updateClickableAreas(Request $request, MachineDrawing $drawing)
    {
        $request->validate([
            'clickable_areas' => 'required|array',
            'clickable_areas.*.x' => 'required|numeric|min:0',
            'clickable_areas.*.y' => 'required|numeric|min:0',
            'clickable_areas.*.width' => 'required|numeric|min:1',
            'clickable_areas.*.height' => 'required|numeric|min:1',
            'clickable_areas.*.component_id' => 'required|exists:components,id',
        ]);
        
        $drawing->update([
            'clickable_areas' => $request->input('clickable_areas')
        ]);
        
        return response()->json([
            'message' => 'Clickable areas updated successfully',
            'drawing' => $drawing->load('clickableComponents')
        ]);
    }
}