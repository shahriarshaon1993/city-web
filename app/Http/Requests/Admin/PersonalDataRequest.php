<?php

namespace App\Http\Requests\Admin;

use App\Support\Privacy\PersonalDataTerm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * NFR-PRIV-03: the phone and/or email to look for. The page, the export and the erase all use this
 * request, so they act on exactly the same person. The values are normalised the way they are stored.
 */
class PersonalDataRequest extends FormRequest
{
    private ?PersonalDataTerm $term = null;

    public function authorize(): bool
    {
        return $this->user()->can('privacy.manage');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'max:190'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $parsed = PersonalDataTerm::fromInput($this->input('phone'), $this->input('email'));

                foreach ($parsed['errors'] as $field => $message) {
                    $validator->errors()->add($field, $message);
                }

                $this->term = $parsed['term'];
            },
        ];
    }

    public function term(): PersonalDataTerm
    {
        return $this->term ?? throw new \LogicException('The request was not validated.');
    }
}
