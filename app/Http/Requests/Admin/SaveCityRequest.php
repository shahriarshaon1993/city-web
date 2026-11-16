<?php

namespace App\Http\Requests\Admin;

use App\Models\Location;
use App\Rules\UniqueSlug;
use App\Support\Slugs\SlugAvailability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** The city dialog (FR-LOC-001, crud.md "Cities / areas"): a name and a URL. Areas have their own editor. */
class SaveCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $city = $this->route('location');

        return $city instanceof Location
            ? $this->user()->can('update', $city)
            : $this->user()->can('create', Location::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name')), 'slug' => trim((string) $this->input('slug'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $city = $this->route('location');
        $id = $city instanceof Location ? $city->id : null;

        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'regex:'.SlugAvailability::PATTERN, new UniqueSlug(Location::class, $id, 'city or area')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['slug.regex' => 'Use lowercase letters, numbers and single hyphens, e.g. dhaka.'];
    }
}
