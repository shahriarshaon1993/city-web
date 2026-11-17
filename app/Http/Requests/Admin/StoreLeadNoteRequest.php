<?php

namespace App\Http\Requests\Admin;

use App\Models\Lead;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * FR-LEAD-009: an internal note, up to 5,000 characters of the light rich text.
 */
class StoreLeadNoteRequest extends FormRequest
{
    public const int MAX = 5000;

    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $lead instanceof Lead && $this->user()->can('update', $lead);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['note' => ['required', 'string', 'max:'.self::MAX]];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['note.required' => 'Write something before you add the note.'];
    }
}
