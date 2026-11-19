<?php

namespace App\Http\Requests\Admin;

use App\Enums\TeamGroup;
use App\Models\TeamMember;
use App\Rules\LibraryImage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** The team member editor (FR-SOC-004): a portrait of at least 800 px, a bio of up to 400 characters. */
class SaveTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $member = $this->route('member');

        return $member instanceof TeamMember
            ? $this->user()->can('update', $member)
            : $this->user()->can('create', TeamMember::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'role_title' => trim((string) $this->input('role_title')),
            'bio' => trim((string) $this->input('bio')),
            'linkedin_url' => trim((string) $this->input('linkedin_url')),
            'email' => trim((string) $this->input('email')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:'.TeamMember::NAME_MAX],
            'role_title' => ['required', 'string', 'min:2', 'max:'.TeamMember::ROLE_MAX],
            'group' => ['required', Rule::enum(TeamGroup::class)],
            'photo_asset_id' => ['nullable', 'integer', new LibraryImage(800)],
            'bio' => ['nullable', 'string', 'max:'.TeamMember::BIO_MAX],
            'linkedin_url' => ['nullable', 'string', 'max:255', 'url:https,http', 'regex:~^https?://([a-z0-9-]+\.)*linkedin\.com(/|$)~i'],
            'email' => ['nullable', 'string', 'max:190', 'email:rfc'],
            'show_email' => ['boolean'],
            'is_published' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['role_title' => 'role', 'photo_asset_id' => 'photo', 'linkedin_url' => 'LinkedIn link', 'show_email' => 'email display'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'linkedin_url.regex' => 'Enter a LinkedIn address, e.g. https://www.linkedin.com/in/name.',
            'linkedin_url.url' => 'Enter a full web address, e.g. https://www.linkedin.com/in/name.',
        ];
    }
}
