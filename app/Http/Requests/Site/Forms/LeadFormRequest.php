<?php

namespace App\Http\Requests\Site\Forms;

use App\Enums\PublishStatus;
use App\Http\Requests\Site\Concerns\ContactFieldRules;
use App\Models\Project;
use App\Models\ProjectUnitType;
use App\Support\Forms\FormOptions;
use App\Support\Forms\FormType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Shared parts of the seven lead forms (spec/leads.md "Lead types and
 * forms"). One concrete Form Request per type (ARC-004); the person, consent
 * and tracking rules live in ContactFieldRules (shared with the job
 * application, 8.2). Defaults follow crud.md VAL-01 where the spec is silent.
 */
abstract class LeadFormRequest extends FormRequest
{
    use ContactFieldRules;

    abstract public function type(): FormType;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * A project slug that is public right now: published and not in the Recycle bin.
     */
    protected static function publishedProject(): Exists
    {
        return Rule::exists('projects', 'slug')
            ->where('publish_status', PublishStatus::Published->value)
            ->where(fn (Builder $query): Builder => $query->where(fn (Builder $inner): Builder => $inner->whereNull('published_at')->orWhere('published_at', '<=', now())))
            ->whereNull('deleted_at');
    }

    /**
     * The project the form was opened for (slug) and, optionally, one of its unit types.
     *
     * @return array<string, array<int, ValidationRule|Closure|string>>
     */
    protected function projectRules(bool $required, bool $unitType = false): array
    {

        return [
            'project' => [$required ? 'required' : 'nullable', 'string', self::publishedProject()],
            ...($unitType ? ['unit_type' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $project = Project::query()->published()->where('slug', (string) $this->input('project'))->value('id');

                    if ($project === null || ! ProjectUnitType::query()->where('project_id', $project)->whereKey((int) $value)->exists()) {
                        $fail('validation.in')->translate();
                    }
                },
            ]] : []),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function messageRules(int $min = 0, bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'string', ...($min > 0 ? ["min:{$min}"] : []), 'max:'.FormOptions::MESSAGE_MAX];
    }
}
