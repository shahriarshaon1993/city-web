<?php

namespace App\Http\Requests\Admin;

use App\Models\Office;
use App\Support\Social\OfficePhones;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** The office editor (FR-SOC-005): address, phones, email, structured hours and the map point. */
class SaveOfficeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $office = $this->route('office');

        return $office instanceof Office
            ? $this->user()->can('update', $office)
            : $this->user()->can('create', Office::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'address' => trim((string) $this->input('address')),
            'city' => trim((string) $this->input('city')),
            'postal_code' => trim((string) $this->input('postal_code')),
            'email' => trim((string) $this->input('email')),
            'phones' => array_values(array_filter(
                array_map(fn (mixed $phone): string => trim((string) $phone), (array) $this->input('phones', [])),
                fn (string $phone): bool => $phone !== '',
            )),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:'.Office::NAME_MAX],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:60'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'phones' => ['required', 'array', 'min:1', 'max:5'],
            'phones.*' => ['string', 'max:30', function (string $attribute, mixed $value, \Closure $fail): void {
                if (OfficePhones::normalize((string) $value) === null) {
                    $fail('Enter a phone number such as 09600-000000 or +880 1700-000000.');
                }
            }],
            'email' => ['nullable', 'string', 'max:190', 'email:rfc'],
            'hours' => ['nullable', 'array', 'max:7'],
            'hours.*.days' => ['required', 'array', 'min:1', 'max:7'],
            'hours.*.days.*' => ['string', 'in:'.implode(',', Office::DAYS), 'distinct'],
            'hours.*.opens' => ['required', 'date_format:H:i'],
            'hours.*.closes' => ['required', 'date_format:H:i', 'after:hours.*.opens'],
            // The same Bangladesh box the project map uses.
            'latitude' => ['nullable', 'numeric', 'between:20.5,26.7', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:88,92.7', 'required_with:latitude'],
            'is_head_office' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'phones.*' => 'phone number',
            'hours.*.days' => 'days',
            'hours.*.opens' => 'opening time',
            'hours.*.closes' => 'closing time',
            'postal_code' => 'postcode',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phones.required' => 'Add at least one phone number.',
            'hours.*.closes.after' => 'Closing time must be after the opening time.',
            'latitude.between' => 'Latitude must be inside Bangladesh (20.5 to 26.7).',
            'longitude.between' => 'Longitude must be inside Bangladesh (88.0 to 92.7).',
        ];
    }
}
