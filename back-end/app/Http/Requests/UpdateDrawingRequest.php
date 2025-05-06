<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDrawingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'drawing_type' => 'sometimes|required|in:exploded,assembly,schematic',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
            'page_number' => 'nullable|integer|min:1',
            'clickable_areas' => 'nullable|array',
            'clickable_areas.*.x' => 'required|numeric|min:0',
            'clickable_areas.*.y' => 'required|numeric|min:0',
            'clickable_areas.*.width' => 'required|numeric|min:1',
            'clickable_areas.*.height' => 'required|numeric|min:1',
            'clickable_areas.*.component_id' => 'required|exists:components,id',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'file.max' => 'The drawing file may not be greater than 10MB.',
            'file.mimes' => 'The drawing must be a file of type: pdf, jpg, jpeg, png.',
            'clickable_areas.*.component_id.exists' => 'One or more selected components do not exist.',
        ];
    }
}