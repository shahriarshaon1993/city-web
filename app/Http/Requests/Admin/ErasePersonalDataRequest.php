<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * NFR-PRIV-03: erasing is for good, so on top of the person's phone and/or email the request carries
 * the word the confirm dialog asked to be typed.
 */
class ErasePersonalDataRequest extends PersonalDataRequest
{
    public const string CONFIRM_WORD = 'ERASE';

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'confirm' => ['required', 'in:'.self::CONFIRM_WORD],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['confirm.in' => 'Type '.self::CONFIRM_WORD.' to confirm.', 'confirm.required' => 'Type '.self::CONFIRM_WORD.' to confirm.'];
    }
}
