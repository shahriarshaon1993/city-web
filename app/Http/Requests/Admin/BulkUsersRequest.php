<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ADM-TBL-009 bulk bar of the users list: either the ticked `ids`, or `all` with the table's
 * filter state ("select all {n} matching").
 */
class BulkUsersRequest extends FormRequest
{
    public const int MAX_IDS = 500;

    public function authorize(): bool
    {
        return $this->user()->can('manage', User::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['activate', 'deactivate'])],
            'all' => ['sometimes', 'boolean'],
            'ids' => ['required_without:all', 'array', 'max:'.self::MAX_IDS],
            'ids.*' => ['integer'],
        ];
    }
}
