<?php

namespace App\Http\Requests\Admin;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** FR-CAR-005: the status select of the application drawer and the row menu. */
class ChangeApplicationStatusRequest extends FormRequest
{
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
        return [
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'undo' => ['sometimes', 'boolean'],
        ];
    }

    public function status(): ApplicationStatus
    {
        return ApplicationStatus::from($this->string('status')->toString());
    }
}
