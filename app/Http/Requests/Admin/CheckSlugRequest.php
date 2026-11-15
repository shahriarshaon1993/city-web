<?php

namespace App\Http\Requests\Admin;

use App\Support\Slugs\SlugAvailability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckSlugRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source' => ['required', 'string', Rule::in(SlugAvailability::sourceKeys())],
            'slug' => ['required', 'string', 'max:120'],
            'ignore' => ['nullable', 'integer'],
        ];
    }
}
