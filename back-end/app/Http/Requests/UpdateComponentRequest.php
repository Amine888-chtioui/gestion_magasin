<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComponentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'machine_id' => 'sometimes|required|exists:machines,id',
            'category_id' => 'nullable|exists:categories,id',
            'pos_number' => 'sometimes|required|string|max:50',
            'quantity' => 'sometimes|required|integer|min:1',
            'unit' => 'sometimes|required|string|max:50',
            'name_de' => 'sometimes|required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'sap_number' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('components')->ignore($this->component),
            ],
            'description' => 'nullable|string',
            'is_spare_part' => 'boolean',
            'is_wearing_part' => 'boolean',
            'specifications' => 'nullable|array',
            'specifications.*.spec_key' => 'required|string|max:255',
            'specifications.*.spec_value' => 'required|string',
            'specifications.*.spec_unit' => 'nullable|string|max:50',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'images_to_delete' => 'nullable|array',
            'images_to_delete.*' => 'exists:component_images,id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'machine_id.exists' => 'The selected machine does not exist.',
            'sap_number.unique' => 'This SAP number is already taken.',
            'quantity.min' => 'Quantity must be at least 1.',
            'images.*.image' => 'The uploaded file must be an image.',
            'images.*.max' => 'Each image may not be greater than 2MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_spare_part')) {
            $this->merge([
                'is_spare_part' => $this->boolean('is_spare_part'),
            ]);
        }

        if ($this->has('is_wearing_part')) {
            $this->merge([
                'is_wearing_part' => $this->boolean('is_wearing_part'),
            ]);
        }
    }
}

// Additional Request Classes you might need:

// File: app/Http/Requests/SearchRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => 'required|string|min:2',
            'type' => 'nullable|in:all,machines,components',
            'per_page' => 'integer|min:5|max:100',
            'filters' => 'nullable|array',
            'filters.machines' => 'nullable|array',
            'filters.machines.*' => 'exists:machines,id',
            'filters.categories' => 'nullable|array',
            'filters.categories.*' => 'exists:categories,id',
            'filters.spare_parts' => 'nullable|boolean',
            'filters.wearing_parts' => 'nullable|boolean',
            'sort' => 'nullable|in:relevance,name,sap_number,pos_number',
            'direction' => 'nullable|in:asc,desc',
        ];
    }
}

// File: app/Http/Requests/FavoriteRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FavoriteRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|in:machine,component',
            'id' => 'required|integer',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'The favorite type is required.',
            'type.in' => 'The favorite type must be either machine or component.',
            'id.required' => 'The item ID is required.',
            'id.integer' => 'The item ID must be an integer.',
        ];
    }
}
