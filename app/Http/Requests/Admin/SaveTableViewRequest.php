<?php

namespace App\Http\Requests\Admin;

use App\Models\SavedTableView;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * ADM-TBL-010: "Save current view". Names are unique per person and table.
 */
class SaveTableViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [SavedTableView::class, (string) $this->input('table_key')]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'table_key' => ['required', 'string', 'max:40'],
            'name' => [
                'required', 'string', 'max:60',
                Rule::unique('saved_table_views', 'name')
                    ->where('user_id', $this->user()->getKey())
                    ->where('table_key', (string) $this->input('table_key')),
            ],
            'state' => ['present', 'array'],
            'is_shared' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['name.unique' => 'You already have a view with this name.'];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->boolean('is_shared') && ! $this->user()->can('share', SavedTableView::class)) {
                    $validator->errors()->add('is_shared', 'Only an administrator can share a view.');
                }
            },
        ];
    }
}
