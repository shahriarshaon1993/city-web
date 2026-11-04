<?php

namespace App\Http\Requests\Admin;

use App\Actions\Users\UpdatePreferences;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'theme' => ['sometimes', 'required', Rule::in(UpdatePreferences::THEMES)],
            'table_density' => ['sometimes', 'required', Rule::in(UpdatePreferences::DENSITIES)],
        ];
    }
}
