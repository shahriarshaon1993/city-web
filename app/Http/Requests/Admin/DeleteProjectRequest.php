<?php

namespace App\Http\Requests\Admin;

use App\Models\Project;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** Delete project (DEL-02): the optional "Redirect visitors to…" address. */
class DeleteProjectRequest extends FormRequest
{
    /** Fixed targets offered next to the other published projects. */
    public const array FIXED_TARGETS = ['/', '/projects', '/locations'];

    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && $this->user()->can('delete', $project);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'redirect_to' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, Closure $fail) use ($project): void {
                if (in_array($value, self::FIXED_TARGETS, true)) {
                    return;
                }

                $slug = str_starts_with((string) $value, '/projects/') ? substr((string) $value, strlen('/projects/')) : null;
                $target = $slug === null ? null : Project::query()->published()->where('slug', $slug)->first();

                if ($target === null || $target->is($project)) {
                    $fail('Choose another published project, or one of the pages in the list.');
                }
            }],
        ];
    }
}
