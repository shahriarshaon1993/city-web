<?php

namespace App\Support\Import\Definitions;

use App\Actions\Social\SaveFaq;
use App\Enums\FaqCategory;
use App\Enums\FaqPlacement;
use App\Models\Faq;
use App\Models\User;
use App\Support\Import\ImportDefinition;
use App\Support\Import\ImportField;
use App\Support\Import\ImportValues;
use App\Support\Import\RowResult;

/**
 * ADM-IO-001 FAQs. New questions arrive as drafts unless the file says otherwise, on the FAQ page
 * unless it names other places; a question that already exists (in any state) is skipped, never
 * changed. Attaching a question to single projects or areas is done in its editor.
 */
final class FaqsImport extends ImportDefinition
{
    public function help(): string
    {
        return 'Questions and answers for the FAQ page. New ones are drafts unless the file says they are published.';
    }

    public function fields(): array
    {
        return [
            new ImportField('question', 'Question', true, ['q', 'title'], 'Can I pay in instalments?'),
            new ImportField('answer', 'Answer', true, ['a', 'reply', 'body'], 'Yes, over up to 60 months.'),
            new ImportField('category', 'Category', false, ['topic', 'group'], 'Payments', 'Buying, Payments, Legal and documents, Buying from abroad, Landowners, After-sales or General'),
            new ImportField('published', 'Published', false, ['is published', 'status', 'visible'], 'no', 'yes or no; empty = no (draft)'),
            new ImportField('show_on', 'Shown on', false, ['places', 'placement', 'show on'], 'FAQ page', 'FAQ page, Project pages, Location pages, Landowner page, NRB page, separated by commas'),
        ];
    }

    public function check(array $row): RowResult
    {
        $question = ImportValues::text($row['question'] ?? '', 400);

        if ($question === null || mb_strlen($question) < 5) {
            return RowResult::fail('The question needs at least 5 characters.');
        }

        if (mb_strlen($question) > Faq::QUESTION_MAX) {
            return RowResult::fail('The question is longer than '.Faq::QUESTION_MAX.' characters.');
        }

        $answer = ImportValues::text($row['answer'] ?? '', 20000);

        if ($answer === null) {
            return RowResult::fail('The answer is empty.');
        }

        $category = $this->category($row['category'] ?? '');

        if ($category === null) {
            return RowResult::fail('“'.trim($row['category'] ?? '').'” is not a category. Use one of the names in the template.');
        }

        $places = $this->places($row['show_on'] ?? '');

        if ($places === null) {
            return RowResult::fail('“'.trim($row['show_on'] ?? '').'” names a place that does not exist.');
        }

        if (Faq::withTrashed()->where('question', $question)->exists()) {
            return RowResult::skip('This question is already in the FAQs.');
        }

        return RowResult::create([
            'question' => $question,
            'answer' => $answer,
            'category' => $category->value,
            'show_on' => $places,
            'is_published' => ImportValues::truthy($row['published'] ?? '', false),
        ]);
    }

    protected function write(User $actor, RowResult $checked, array $options): RowResult
    {
        app(SaveFaq::class)->handle($actor, $checked->data);

        return $checked;
    }

    private function category(string $text): ?FaqCategory
    {
        $text = mb_strtolower(trim($text));

        if ($text === '') {
            return FaqCategory::General;
        }

        foreach (FaqCategory::cases() as $category) {
            if ($text === $category->value || $text === mb_strtolower($category->label())) {
                return $category;
            }
        }

        return null;
    }

    /**
     * @return list<string>|null null when a name is not a place
     */
    private function places(string $text): ?array
    {
        if (trim($text) === '') {
            return [FaqPlacement::FaqPage->value];
        }

        $places = [];

        foreach (preg_split('/[,;|]/', $text) ?: [] as $part) {
            $part = mb_strtolower(trim($part));

            if ($part === '') {
                continue;
            }

            $match = collect(FaqPlacement::cases())->first(fn (FaqPlacement $place): bool => $part === $place->value || $part === mb_strtolower($place->label()));

            if ($match === null) {
                return null;
            }

            $places[] = $match->value;
        }

        return array_values(array_unique($places)) ?: [FaqPlacement::FaqPage->value];
    }
}
