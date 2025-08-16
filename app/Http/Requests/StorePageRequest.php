<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Will be handled by policies
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'summary' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'status' => 'required|in:draft,published',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:320',
            'meta_keywords' => 'nullable|array',
            'meta_keywords.*' => 'string|max:50',
            'published_at' => 'nullable|date|after_or_equal:now',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The page title is required.',
            'slug.unique' => 'This slug is already in use. Please choose a different one.',
            'meta_description.max' => 'Meta description should not exceed 320 characters for optimal SEO.',
            'published_at.after_or_equal' => 'Published date must be in the future or current time.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('meta_keywords') && is_string($this->meta_keywords)) {
            $this->merge([
                'meta_keywords' => array_filter(array_map('trim', explode(',', $this->meta_keywords))),
            ]);
        }
    }
}
