<?php

namespace App\Http\Requests\Admin;

use App\Models\NotFoundLog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ADM-TBL-009 bulk bar of the 404 monitor (ignore, un-ignore, delete for good).
 */
class BulkNotFoundRequest extends FormRequest
{
    public const int MAX_IDS = 500;

    public function authorize(): bool
    {
        return $this->user()->can('manage', NotFoundLog::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['ignore', 'unignore', 'delete'])],
            'all' => ['sometimes', 'boolean'],
            'ids' => ['required_without:all', 'array', 'max:'.self::MAX_IDS],
            'ids.*' => ['integer'],
        ];
    }
}
