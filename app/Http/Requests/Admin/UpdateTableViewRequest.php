<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * ADM-TBL-010: overwrite a view with the table as it is now, and/or set or clear its default flag.
 */
class UpdateTableViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('view'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'state' => ['sometimes', 'array'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
