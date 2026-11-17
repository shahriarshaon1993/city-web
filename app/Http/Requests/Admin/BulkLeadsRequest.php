<?php

namespace App\Http\Requests\Admin;

use App\Models\Lead;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ADM-TBL-009 bulk bar of the leads list: the ticked `ids`, or `all` with the table's filter state.
 * `assign` carries the new owner (empty = unassigned). Each lead is still checked against its Policy.
 */
class BulkLeadsRequest extends FormRequest
{
    public const int MAX_IDS = 500;

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Lead::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['assign', 'delete'])],
            'assignee_id' => ['nullable', 'integer'],
            'all' => ['sometimes', 'boolean'],
            'ids' => ['required_without:all', 'array', 'max:'.self::MAX_IDS],
            'ids.*' => ['integer'],
        ];
    }
}
