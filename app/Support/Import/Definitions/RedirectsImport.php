<?php

namespace App\Support\Import\Definitions;

use App\Actions\Redirects\SaveRedirect;
use App\Models\Redirect;
use App\Models\User;
use App\Support\Import\ImportDefinition;
use App\Support\Import\ImportField;
use App\Support\Import\ImportValues;
use App\Support\Import\RowResult;
use App\Support\Redirects\RedirectPath;
use App\Support\Redirects\RedirectRules;
use App\Support\Redirects\RuleSet;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * ADM-IO-001 redirects (SEO-TEC-008): the same file the Redirects screen exports. A source that already
 * has a rule updates it; one in the Recycle bin fails. Every row passes the rules of the dialog, and
 * loops and chains are caught by `SaveRedirect` against the rules saved so far in this run.
 */
final class RedirectsImport extends ImportDefinition
{
    private ?RuleSet $rules = null;

    public function help(): string
    {
        return 'Old addresses and where they should go, in the same layout the Redirects screen exports. An address that already has a rule is updated.';
    }

    public function fields(): array
    {
        return [
            new ImportField('source', 'Source', true, ['from', 'old', 'old address', 'old url'], '/old-page', 'The old address on this site'),
            new ImportField('target', 'Target', false, ['to', 'destination', 'new', 'new address', 'new url'], '/new-page', 'Not needed for code 410'),
            new ImportField('code', 'Code', false, ['type', 'status'], '301', '301, 302, 307, 308 or 410; empty = 301'),
            new ImportField('active', 'Active', false, ['is active'], 'yes', 'yes or no; empty = yes'),
        ];
    }

    public function check(array $row): RowResult
    {
        $values = [
            'source' => trim($row['source'] ?? ''),
            'target' => trim($row['target'] ?? ''),
            'code' => trim($row['code'] ?? '') === '' ? 301 : trim($row['code']),
            'is_active' => ImportValues::truthy($row['active'] ?? '', true),
        ];

        $validator = Validator::make($values, RedirectRules::rules($values, unique: false), RedirectRules::messages(), RedirectRules::attributes());

        if ($validator->fails()) {
            return RowResult::fail((string) $validator->errors()->first());
        }

        /** @var array<string, mixed> $validated */
        $validated = $validator->validated();
        $parsed = RedirectPath::parseSource((string) $validated['source']);
        $existing = Redirect::withTrashed()->where('source', $parsed['source'] ?? '')->first();

        if ($existing?->trashed()) {
            return RowResult::fail('This address belongs to a redirect in the Recycle bin. Restore it or delete it forever first.');
        }

        return $existing === null
            ? RowResult::create(['values' => $validated])
            : RowResult::update(['values' => $validated, 'id' => $existing->id]);
    }

    protected function write(User $actor, RowResult $checked, array $options): RowResult
    {
        $this->rules ??= RuleSet::fromDatabase();

        /** @var array<string, mixed> $values */
        $values = $checked->data['values'];
        $existing = isset($checked->data['id']) ? Redirect::query()->find((int) $checked->data['id']) : null;

        try {
            $saved = app(SaveRedirect::class)->handle($actor, $values, $existing, $this->rules, log: false);
        } catch (ValidationException $exception) {
            return RowResult::fail((string) $exception->validator->errors()->first());
        }

        $redirect = $saved['redirect'];
        $this->rules = $redirect->is_active ? $this->rules->withRule($redirect) : $this->rules->without($redirect->id);

        return $checked;
    }
}
