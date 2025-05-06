<?php

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