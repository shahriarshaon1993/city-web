<?php

namespace App\Http\Requests\Admin;

use App\Models\Redirect;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * CSV import of redirects (SEO-TEC-008): one file, up to 2 MB, in the format the export writes.
 */
class ImportRedirectsRequest extends FormRequest
{
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
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Choose a CSV file to import.',
            'file.mimes' => 'The file must be a CSV (.csv).',
            'file.max' => 'The file must be 2 MB or smaller.',
        ];
    }
}
