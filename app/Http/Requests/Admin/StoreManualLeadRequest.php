<?php

namespace App\Http\Requests\Admin;

use App\Models\Lead;
use App\Models\ProjectUnitType;
use App\Queries\Admin\LeadAssignees;
use App\Support\Forms\Countries;
use App\Support\Forms\PhoneNumber;
use App\Support\Leads\LeadReasons;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * DEC-33: the "New lead" dialog for phone and walk-in enquiries. The phone follows FR-LEAD-002 (the
 * selected country decides how a national number is read) and is stored as E.164. Choosing the owner
 * needs `leads.assign`.
 */
class StoreManualLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Lead::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone_country' => ['required', Rule::in(array_column(Countries::all(), 'code'))],
            'phone' => ['required', 'string', 'max:30'],
            'whatsapp' => ['sometimes', 'boolean'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'channel' => ['required', Rule::in(array_keys(LeadReasons::CHANNELS))],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'unit_type_id' => ['nullable', 'integer', function (string $attribute, mixed $value, Closure $fail): void {
                $belongs = $value === null || $value === ''
                    || ProjectUnitType::query()->whereKey($value)->where('project_id', $this->input('project_id'))->exists();

                if (! $belongs) {
                    $fail('Choose a unit type of the selected project.');
                }
            }],
            'message' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'integer', function (string $attribute, mixed $value, Closure $fail): void {
                if ($value === null || $value === '') {
                    return;
                }

                if (! $this->user()->can('leads.assign')) {
                    $fail('You can not choose who gets this lead.');
                } elseif (! app(LeadAssignees::class)->get()->contains('id', (int) $value)) {
                    $fail('Choose someone from the list who can work on leads.');
                }
            }],
            'follow_up_at' => ['nullable', 'date_format:'.SetLeadFollowUpRequest::FORMAT, function (string $attribute, mixed $value, Closure $fail): void {
                if (Carbon::createFromFormat(SetLeadFollowUpRequest::FORMAT, (string) $value, SetLeadFollowUpRequest::ZONE)->lessThan(now(SetLeadFollowUpRequest::ZONE)->startOfDay())) {
                    $fail('Choose today or a later day.');
                }
            }],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['phone_country' => 'country', 'follow_up_at' => 'follow-up', 'assigned_to' => 'owner', 'unit_type_id' => 'unit type'];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('phone') || $validator->errors()->has('phone_country')) {
                    return;
                }

                if ($this->phone() === null) {
                    $validator->errors()->add('phone', $this->input('phone_country') === Countries::DEFAULT
                        ? 'Enter a Bangladesh mobile number such as 01712 345678.'
                        : 'Enter a valid phone number for the selected country.');
                }
            },
        ];
    }

    public function phone(): ?string
    {
        return PhoneNumber::normalize((string) $this->input('phone'), (string) $this->input('phone_country', Countries::DEFAULT));
    }

    public function followUpAt(): ?Carbon
    {
        $value = $this->validated('follow_up_at');

        return $value === null || $value === '' ? null : Carbon::createFromFormat(SetLeadFollowUpRequest::FORMAT, (string) $value, SetLeadFollowUpRequest::ZONE)->utc();
    }
}
