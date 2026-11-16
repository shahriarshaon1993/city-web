<?php

namespace App\Http\Requests\Admin;

use App\Models\Redirect;
use App\Support\Redirects\RedirectRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The redirect dialog (SEO-TEC-008): create and edit share the rules (`RedirectRules`), which the
 * CSV import uses too. Loops, chains and the 2,000-rule cap are decided by `SaveRedirect`.
 */
class SaveRedirectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $redirect = $this->route('redirect');

        return $redirect instanceof Redirect
            ? $this->user()->can('update', $redirect)
            : $this->user()->can('create', Redirect::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'source' => trim((string) $this->input('source')),
            'target' => trim((string) $this->input('target')) === '' ? null : trim((string) $this->input('target')),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $redirect = $this->route('redirect');

        return RedirectRules::rules($this->all(), $redirect instanceof Redirect ? $redirect->id : null);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return RedirectRules::messages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return RedirectRules::attributes();
    }
}
