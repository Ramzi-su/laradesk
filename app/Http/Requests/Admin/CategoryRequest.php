<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Shared by store and update: the rules are identical, only the
 * unique check must ignore the category being edited.
 */
class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Category|null $category */
        $category = $this->route('category');

        return $category === null
            ? $this->user()->can('create', Category::class)
            : $this->user()->can('update', $category);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug((string) $this->input('name'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ignore = $this->route('category')?->getKey();

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', Rule::unique('categories', 'slug')->ignore($ignore)],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['slug' => __('name')];
    }
}
