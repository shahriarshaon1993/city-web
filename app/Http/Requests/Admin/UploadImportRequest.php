<?php

namespace App\Http\Requests\Admin;

use App\Support\Import\ImportFile;
use App\Support\Import\ImportType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ADM-IO-001 step 1: which kind of data, and the file (CSV or XLSX, up to 10 MB). Whether the signed-in
 * person may import that kind is the same check the wizard page uses.
 */
class UploadImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = ImportType::tryFrom((string) $this->input('type'));

        return $type === null || $type->canBeUsedBy($this->user());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ImportType::class)],
            'file' => ['required', 'file', 'max:'.(ImportFile::MAX_BYTES / 1024), 'extensions:csv,txt,xlsx'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Choose what you are importing.',
            'file.required' => 'Choose a CSV or Excel file.',
            'file.max' => 'The file must be 10 MB or smaller.',
            'file.extensions' => 'The file must be a CSV (.csv) or Excel (.xlsx) file.',
            'file.uploaded' => 'The file could not be uploaded. It must be 10 MB or smaller.',
        ];
    }

    public function importType(): ImportType
    {
        return ImportType::from($this->string('type')->toString());
    }
}
