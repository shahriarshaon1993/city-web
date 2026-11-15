<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\Users\UserGuard;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Invite dialog (ADM-AUTH-006, ADM-SCR-16); limits follow the `users` columns (VAL-01).
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', Rule::unique(User::class)],
            'role' => ['required', 'string', Rule::in(UserGuard::assignableRoles($this->user()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $binned = User::onlyTrashed()->where('email', $this->input('email'))->first();

        return [
            // DEL-08: a taken value that sits in the Recycle bin says so.
            'email.unique' => $binned !== null
                ? __('This email address belongs to “:name” in the Recycle bin — restore them or delete them forever.', ['name' => $binned->name])
                : 'Someone with this email address already has an account.',
            'role.in' => 'Choose one of the roles you may assign.',
        ];
    }
}
