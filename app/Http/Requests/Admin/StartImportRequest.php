<?php

namespace App\Http\Requests\Admin;

use App\Models\ImportRun;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * ADM-IO-001 step 4: the confirmations an import type asks for (the Action checks the required ones).
 */
class StartImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ImportRun $run */
        $run = $this->route('run');

        return $this->user()->can('update', $run);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'confirm' => ['nullable', 'array'],
            'confirm.*' => ['boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function confirmed(): array
    {
        return (array) $this->validated('confirm', []);
    }
}
