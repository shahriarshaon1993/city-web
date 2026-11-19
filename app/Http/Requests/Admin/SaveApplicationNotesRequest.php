<?php

namespace App\Http\Requests\Admin;

use App\Models\JobApplication;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** FR-CAR-005: the internal notes box of the application drawer (plain text, never shown to the applicant). */
class SaveApplicationNotesRequest extends FormRequest
{
    public const int MAX = 5000;

    public function authorize(): bool
    {
        $application = $this->route('application');

        return $application instanceof JobApplication && $this->user()->can('update', $application);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['notes' => ['nullable', 'string', 'max:'.self::MAX]];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['notes.max' => 'Notes can be at most '.self::MAX.' characters.'];
    }
}
