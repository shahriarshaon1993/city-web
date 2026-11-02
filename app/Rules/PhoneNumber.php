<?php

namespace App\Rules;

use App\Support\Forms\Countries;
use App\Support\Forms\PhoneNumber as Phone;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * FR-LEAD-002: validates the field against the country picked in
 * `{field}_country` (Bangladesh when absent).
 */
class PhoneNumber implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $country = $this->data["{$attribute}_country"] ?? Countries::DEFAULT;
        $country = is_string($country) ? $country : Countries::DEFAULT;

        if (! is_string($value) || Phone::normalize($value, $country) === null) {
            $fail($country === Countries::DEFAULT ? 'validation.phone_bd' : 'validation.phone_intl')->translate();
        }
    }
}
