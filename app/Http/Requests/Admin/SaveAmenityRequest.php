<?php

namespace App\Http\Requests\Admin;

use App\Enums\AmenityCategory;
use App\Models\Amenity;
use App\Support\Amenities\AmenityIcons;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** The amenity dialog (ADM-SCR-03): name, category and a Lucide icon from the allowlist. */
class SaveAmenityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $amenity = $this->route('amenity');

        return $amenity instanceof Amenity
            ? $this->user()->can('update', $amenity)
            : $this->user()->can('create', Amenity::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $amenity = $this->route('amenity');

        return [
            // Unique including the Recycle bin (DEL-08), because the slug column is unique too.
            'name' => ['required', 'string', 'min:2', 'max:'.Amenity::NAME_MAX, Rule::unique('amenities', 'name')->ignore($amenity instanceof Amenity ? $amenity->id : null)],
            'category' => ['required', Rule::enum(AmenityCategory::class)],
            'icon' => ['required', 'string', Rule::in(AmenityIcons::names())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'An amenity with this name already exists (it may be in the Recycle bin).',
            'icon.in' => 'Pick an icon from the list.',
        ];
    }
}
