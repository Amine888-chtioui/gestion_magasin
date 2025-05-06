<?php

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