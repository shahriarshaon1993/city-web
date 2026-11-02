<?php

namespace App\Http\Requests\Site\Forms;

use App\Support\Forms\FormOptions;
use App\Support\Forms\FormType;
use Illuminate\Validation\Rule;

/**
 * `landowner` (PG-10): land details and up to three private documents
 * (PDF/JPG/PNG, 5 MB each, FR-LEAD-011). Files are checked by content
 * (`mimetypes`); they are stored from 15.1 on.
 */
class LandownerRequest extends LeadFormRequest
{
    public function type(): FormType
    {
        return FormType::Landowner;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->personRules(),
            'land_location' => ['required', 'string', 'max:190'],
            'land_size_katha' => ['required', 'numeric', 'gt:0', 'max:10000'],
            'road_width_ft' => ['nullable', 'integer', 'min:1', 'max:500'],
            'plot_facing' => ['nullable', Rule::in(FormOptions::values('facing'))],
            'ownership' => ['nullable', Rule::in(FormOptions::values('ownership'))],
            'documents' => ['nullable', 'array', 'max:'.FormOptions::MAX_FILES],
            'documents.*' => [
                'file',
                'mimetypes:'.implode(',', FormOptions::FILE_MIMES),
                'max:'.FormOptions::MAX_FILE_KB,
            ],
            'message' => $this->messageRules(),
            ...$this->consentRules(),
            ...$this->trackingRules(),
        ];
    }
}
