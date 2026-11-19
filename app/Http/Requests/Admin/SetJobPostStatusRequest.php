<?php

namespace App\Http\Requests\Admin;

use App\Enums\JobStatus;
use App\Models\JobPost;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** The row-menu verbs of the jobs list: Open, Close applications, Back to draft (ADM-SCR-10). */
class SetJobPostStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $job = $this->route('job');

        return $job instanceof JobPost && $this->user()->can('update', $job);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(JobStatus::class)]];
    }

    public function status(): JobStatus
    {
        return JobStatus::from($this->string('status')->toString());
    }
}
