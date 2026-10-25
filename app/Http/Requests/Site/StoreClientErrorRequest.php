<?php

namespace App\Http\Requests\Site;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A browser error report (SEO-ANA-006, HERO-028). Anonymous by design.
 */
class StoreClientErrorRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/'],
            'message' => ['required', 'string', 'max:500'],
            'url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
