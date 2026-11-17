<?php

namespace App\Http\Requests\Admin;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Support\Leads\LeadReasons;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FR-LEAD-006: the new status of a lead. Won and Lost need a reason from their list (the note is
 * optional, 200 characters with the reason). The same request carries the Undo of TOAST-02: the
 * previous status and the text that went with it.
 */
class ChangeLeadStatusRequest extends FormRequest
{
    public const int NOTE_MAX = 200;

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
        $status = LeadStatus::tryFrom((string) $this->input('status'));
        $reasons = $status === LeadStatus::Won ? LeadReasons::WON : LeadReasons::LOST;

        return [
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'reason' => [Rule::requiredIf(($status?->needsReason() ?? false) && ! $this->boolean('undo')), 'nullable', Rule::in(array_keys($reasons))],
            'note' => ['nullable', 'string', 'max:'.self::NOTE_MAX],
            'undo' => ['sometimes', 'boolean'],
            'lost_reason' => ['nullable', 'string', 'max:200'],
            'won_note' => ['nullable', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Choose a reason before you close the lead.',
            'reason.in' => 'Choose one of the reasons in the list.',
        ];
    }

    public function status(): LeadStatus
    {
        return LeadStatus::from($this->string('status')->toString());
    }
}
