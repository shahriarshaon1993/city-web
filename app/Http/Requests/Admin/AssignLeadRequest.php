<?php

namespace App\Http\Requests\Admin;

use App\Models\Lead;
use App\Models\User;
use App\Queries\Admin\LeadAssignees;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * FR-LEAD-007: hand a lead to an active user who works on leads, or take the owner away (empty).
 */
class AssignLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $lead instanceof Lead && $this->user()->can('assign', $lead);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assignee_id' => ['nullable', 'integer', function (string $attribute, mixed $value, Closure $fail): void {
                if (! app(LeadAssignees::class)->get()->contains('id', (int) $value)) {
                    $fail('Choose someone from the list who can work on leads.');
                }
            }],
        ];
    }

    public function assignee(): ?User
    {
        $id = $this->integer('assignee_id');

        return $id === 0 ? null : User::query()->find($id);
    }
}
