<?php

namespace App\Actions\Redirects;

use App\Actions\Activity\LogActivity;
use App\Models\Redirect;
use App\Models\User;
use App\Support\Redirects\RedirectPath;
use App\Support\Redirects\RedirectRules;
use App\Support\Redirects\RuleSet;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * SEO-TEC-008 / ADM-IO-001: imports redirects from a CSV with the columns Source, Target, Code and
 * Active (the same file the list exports). A source that already has a rule updates it; a source in
 * the Recycle bin is skipped. Every row is checked with the same rules as the dialog; rows that fail
 * are skipped and reported with their row number, the rest are saved.
 */
final class ImportRedirects
{
    /** Header names accepted for each column (lower case). */
    private const array HEADERS = [
        'source' => ['source', 'from', 'old', 'old address', 'old url'],
        'target' => ['target', 'to', 'destination', 'new', 'new address', 'new url'],
        'code' => ['code', 'type', 'status'],
        'is_active' => ['active', 'is_active'],
    ];

    /** Rows read from a file; the rule cap is 2,000 anyway. */
    private const int MAX_ROWS = 2500;

    public function __construct(
        private readonly SaveRedirect $saveRedirect,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<array{row: int, source: string, message: string}>}
     *
     * @throws ValidationException when the file has no Source column
     */
    public function handle(User $actor, string $path): array
    {
        $reader = SimpleExcelReader::create($path, 'csv')->trimHeaderRow()->formatHeadersUsing(fn (string $header): string => mb_strtolower(trim($header)));
        $columns = $this->columns(array_values($reader->getHeaders() ?? []));

        if (! isset($columns['source'])) {
            throw ValidationException::withMessages(['file' => 'The file needs a Source column in its first row (and a Target column), like the export.']);
        }

        $rules = RuleSet::fromDatabase();
        $report = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        $row = 1;

        foreach ($reader->getRows()->take(self::MAX_ROWS) as $cells) {
            $row++;
            $input = $this->input($cells, $columns);

            if ($input['source'] === '') {
                continue;
            }

            try {
                $result = $this->import($actor, $input, $rules);
            } catch (ValidationException $exception) {
                $report['skipped']++;
                $report['errors'][] = ['row' => $row, 'source' => $input['source'], 'message' => $exception->validator->errors()->first()];

                continue;
            }

            $rules = $result['rules'];
            $report[$result['event']]++;
        }

        $reader->close();

        $this->logActivity->handle('redirects', 'imported', "Redirects imported: {$report['created']} new, {$report['updated']} updated, {$report['skipped']} skipped", $actor, null, [
            'created' => $report['created'],
            'updated' => $report['updated'],
            'skipped' => $report['skipped'],
        ]);

        return $report;
    }

    /**
     * @param  array{source: string, target: string, code: string, is_active: string}  $input
     * @return array{event: 'created'|'updated', rules: RuleSet}
     */
    private function import(User $actor, array $input, RuleSet $rules): array
    {
        $values = [
            'source' => $input['source'],
            'target' => $input['target'],
            'code' => $input['code'] === '' ? 301 : $input['code'],
            'is_active' => $this->truthy($input['is_active']),
        ];

        $validated = Validator::make($values, RedirectRules::rules($values, unique: false), RedirectRules::messages(), RedirectRules::attributes())->validate();
        $parsed = RedirectPath::parseSource($validated['source']);
        $existing = Redirect::withTrashed()->where('source', $parsed['source'] ?? '')->first();

        if ($existing?->trashed()) {
            throw ValidationException::withMessages(['source' => 'This address belongs to a redirect in the Recycle bin — restore it or delete it forever.']);
        }

        $saved = $this->saveRedirect->handle($actor, $validated, $existing, $rules, log: false);
        $redirect = $saved['redirect'];

        return [
            'event' => $existing === null ? 'created' : 'updated',
            'rules' => $redirect->is_active ? $rules->withRule($redirect) : $rules->without($redirect->id),
        ];
    }

    /**
     * @param  list<string>  $headers
     * @return array<string, string> field → the file's header
     */
    private function columns(array $headers): array
    {
        $found = [];

        foreach (self::HEADERS as $field => $names) {
            foreach ($headers as $header) {
                if (in_array($header, $names, true)) {
                    $found[$field] = $header;

                    break;
                }
            }
        }

        return $found;
    }

    /**
     * @param  array<string, mixed>  $cells
     * @param  array<string, string>  $columns
     * @return array{source: string, target: string, code: string, is_active: string}
     */
    private function input(array $cells, array $columns): array
    {
        $value = fn (string $field): string => isset($columns[$field]) ? trim((string) ($cells[$columns[$field]] ?? '')) : '';

        return [
            'source' => $value('source'),
            'target' => $value('target'),
            'code' => $value('code'),
            'is_active' => $value('is_active'),
        ];
    }

    private function truthy(string $value): bool
    {
        return ! in_array(mb_strtolower($value), ['no', 'n', '0', 'false', 'off', 'inactive'], true);
    }
}
