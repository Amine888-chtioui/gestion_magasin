<?php

// File: app/Http/Requests/StoreMachineRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMachineRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'sap_number' => 'nullable|string|max:255|unique:machines,sap_number',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'company' => 'required|string|max:255',
            'drawings' => 'nullable|array',
            'drawings.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
            'drawing_types' => 'nullable|array',
            'drawing_types.*' => 'string|in:exploded,assembly,schematic',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Machine name is required.',
            'model.required' => 'Machine model is required.',
            'image.image' => 'The uploaded file must be an image.',
            'image.max' => 'The image may not be greater than 2MB.',
            'drawings.*.max' => 'Each drawing may not be greater than 5MB.',
        ];
    }
}

// File: app/Http/Requests/UpdateMachineRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMachineRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:255',
            'model' => 'sometimes|required|string|max:255',
            'sap_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('machines')->ignore($this->machine),
            ],
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'company' => 'sometimes|required|string|max:255',
            'drawings' => 'nullable|array',
            'drawings.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
            'drawing_types' => 'nullable|array',
            'drawing_types.*' => 'string|in:exploded,assembly,schematic',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'image.image' => 'The uploaded file must be an image.',
            'image.max' => 'The image may not be greater than 2MB.',
            'drawings.*.max' => 'Each drawing may not be greater than 5MB.',
            'sap_number.unique' => 'This SAP number is already taken.',
        ];
    }
}

// File: app/Http/Requests/StoreComponentRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComponentRequest extends FormRequest
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
            'machine_id' => 'required|exists:machines,id',
            'category_id' => 'nullable|exists:categories,id',
            'pos_number' => 'required|string|max:50',
            'quantity' => 'required|integer|min:1',
            'unit' => 'required|string|max:50',
            'name_de' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'sap_number' => 'required|string|max:255|unique:components,sap_number',
            'description' => 'nullable|string',
            'is_spare_part' => 'boolean',
            'is_wearing_part' => 'boolean',
            'specifications' => 'nullable|array',
            'specifications.*.spec_key' => 'required|string|max:255',
            'specifications.*.spec_value' => 'required|string',
            'specifications.*.spec_unit' => 'nullable|string|max:50',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'machine_id.required' => 'Please select a machine for this component.',
            'machine_id.exists' => 'The selected machine does not exist.',
            'pos_number.required' => 'Position number is required.',
            'quantity.required' => 'Quantity is required.',
            'quantity.min' => 'Quantity must be at least 1.',
            'name_de.required' => 'German name is required.',
            'sap_number.required' => 'SAP number is required.',
            'sap_number.unique' => 'This SAP number is already taken.',
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