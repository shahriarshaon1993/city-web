<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ADM-SCR-20 profile form; limits follow the `users` columns (VAL-01).
 */
class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'string', 'email:rfc', 'max:190',
                Rule::unique(User::class)->ignore($this->user()->getKey()),
            ],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9][0-9 \-]{5,19}$/'],
            // A raster image or SVG from the library; trashed items are not offered.
            'avatar_asset_id' => [
                'nullable', 'integer',
                Rule::exists('media_assets', 'id')->whereNull('deleted_at')->whereIn('kind', ['image', 'svg']),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a phone number with digits, spaces or dashes, e.g. +880 1712-345678.',
            'avatar_asset_id.exists' => 'Choose an image from the library; that file is gone or is not an image.',
        ];
    }
}
