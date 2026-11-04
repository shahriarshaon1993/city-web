<?php

namespace App\Http\Requests\Admin;

use App\Support\Auth\PermissionMatrix;
use App\Support\Auth\SystemRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * Role editor (ADM-SCR-16). A custom role is a name plus a set of seeded permissions.
 */
class SaveRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role
            ? $this->user()->can('update', $role)
            : $this->user()->can('create', Role::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => [
                'required', 'string', 'max:60',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role instanceof Role ? $role->getKey() : null),
                Rule::notIn(SystemRole::names()),
            ],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::in(PermissionMatrix::all())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A role with this name already exists.',
            'name.not_in' => 'This name belongs to a system role. Choose another.',
        ];
    }
}
