<?php

namespace App\Http\Requests\Admin;

use App\Models\ImportRun;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * ADM-IO-001 step 2: field → column index. Unknown fields and columns are dropped by the Action.
 */
class SaveImportMappingRequest extends FormRequest
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
            'mapping' => ['present', 'array'],
            'mapping.*' => ['nullable', 'integer', 'min:0', 'max:500'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mapping(): array
    {
        return (array) $this->validated('mapping');
    }
}
