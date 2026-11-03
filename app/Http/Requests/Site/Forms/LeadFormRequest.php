<?php

namespace App\Http\Requests\Site\Forms;

use App\Http\Requests\Site\Concerns\ContactFieldRules;
use App\Support\Demo\DemoProjectDetail;
use App\Support\Demo\DemoProjects;
use App\Support\Forms\FormOptions;
use App\Support\Forms\FormType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * The project the form was opened for (slug) and, optionally, one of its unit types.
     *
     * @return array<string, array<int, ValidationRule|Closure|string>>
     */
    protected function projectRules(bool $required, bool $unitType = false): array
    {
        $slugs = array_column(DemoProjects::rows(), 'slug');

        return [
            'project' => [$required ? 'required' : 'nullable', 'string', Rule::in($slugs)],
            ...($unitType ? ['unit_type' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $units = DemoProjectDetail::find((string) $this->input('project'))['unit_types'] ?? [];

                    if (! in_array((int) $value, array_column($units, 'id'), true)) {
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
