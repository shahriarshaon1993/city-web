<?php

namespace App\Http\Requests\Admin;

use App\Models\RecycleBinEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Bulk restore / delete forever from the bin: the ticked `ids`, or `all` with the table's filter
 * state ("select all {n} matching", ADM-TBL-009). `undo` marks the Undo of a bulk delete toast.
 */
class RecycleBinBulkRequest extends FormRequest
{
    public const int MAX_IDS = 500;

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', RecycleBinEntry::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'all' => ['sometimes', 'boolean'],
            'ids' => ['required_without:all', 'array', 'max:'.self::MAX_IDS],
            'ids.*' => ['integer'],
            'undo' => ['sometimes', 'boolean'],
        ];
    }
}
