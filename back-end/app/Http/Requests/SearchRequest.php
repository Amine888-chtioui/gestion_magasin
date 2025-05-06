<?php

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
