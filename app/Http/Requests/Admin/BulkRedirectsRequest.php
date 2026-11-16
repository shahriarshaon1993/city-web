<?php

namespace App\Http\Requests\Admin;

use App\Models\Redirect;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ADM-TBL-009 bulk bar of the redirects list: the ticked `ids`, or `all` with the table's filter
 * state ("select all {n} matching").
 */
class BulkRedirectsRequest extends FormRequest
{
    public const int MAX_IDS = 500;

    public function authorize(): bool
    {
        return $this->user()->can('create', Redirect::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
            'all' => ['sometimes', 'boolean'],
            'ids' => ['required_without:all', 'array', 'max:'.self::MAX_IDS],
            'ids.*' => ['integer'],
        ];
    }
}
