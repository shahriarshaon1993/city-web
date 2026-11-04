<?php

namespace App\Http\Requests\Admin;

use App\Support\Users\UserGuard;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edit dialog of the users list. The email stays as invited; people change their own on the profile.
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9][0-9 \-]{5,19}$/'],
            'role' => ['required', 'string', Rule::in(UserGuard::assignableRoles($this->user()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a phone number with digits, spaces or dashes, e.g. +880 1712-345678.',
            'role.in' => 'Choose one of the roles you may assign.',
        ];
    }
}
