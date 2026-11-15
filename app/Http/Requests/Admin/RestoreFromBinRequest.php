<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Restore one bin item. `resolution` answers the item's restore conflicts (DEL-05: a new slug,
 * or `true` for "restore the parent too"); `undo` marks the Undo button of a delete toast (TOAST-02).
 * The Policy is checked per entry in the controller.
 */
class RestoreFromBinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resolution' => ['sometimes', 'nullable', 'array', 'max:10'],
            'resolution.*' => ['nullable'],
            'undo' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolution(): array
    {
        return (array) ($this->validated('resolution') ?? []);
    }
}
