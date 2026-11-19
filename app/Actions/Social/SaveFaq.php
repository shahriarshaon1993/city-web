<?php

namespace App\Actions\Social;

use App\Actions\Activity\LogActivity;
use App\Models\FacetPage;
use App\Models\Faq;
use App\Models\Location;
use App\Models\Project;
use App\Models\User;
use App\Support\Html\BasicHtml;
use Illuminate\Support\Facades\DB;

/**
 * FR-SOC-003: creates or edits a question. The answer keeps only paragraphs, bold, links and lists;
 * `project_ids` / `location_ids` attach it to single pages (`faqables`), in the order of the lists.
 */
final class SaveFaq
{
    /** The limited rich text of FR-SOC-003. */
    private const string ANSWER_TAGS = '<p><br><ul><ol><li><strong><b><a>';

    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  array<string, mixed>  $data  validated form values
     * @return array{faq: Faq, changed: bool}
     */
    public function handle(User $actor, array $data, ?Faq $faq = null): array
    {
        return DB::transaction(function () use ($actor, $data, $faq): array {
            $creating = $faq === null;
            $faq ??= new Faq(['sort_order' => ((int) Faq::query()->max('sort_order')) + 1]);

            $faq->fill([
                'question' => trim((string) $data['question']),
                'answer' => self::cleanAnswer((string) $data['answer']),
                'category' => $data['category'],
                'show_on' => array_values(array_unique($data['show_on'] ?? [])),
                'is_published' => (bool) ($data['is_published'] ?? false),
            ]);

            $attachments = $this->attachments($data);
            $attachmentsChanged = ! $creating && $attachments !== $this->currentAttachments($faq);
            $changed = $creating || $faq->isDirty() || $attachmentsChanged;

            if ($changed) {
                $faq->save();
                $this->syncAttachments($faq, $attachments);

                $this->logActivity->handle(
                    'faqs',
                    $creating ? 'created' : 'updated',
                    ($creating ? 'FAQ created: ' : 'FAQ updated: ').$faq->question,
                    $actor,
                    $faq,
                    ['label' => $faq->question],
                );
            }

            return ['faq' => $faq, 'changed' => $changed];
        });
    }

    public static function cleanAnswer(string $answer): string
    {
        return strip_tags((string) BasicHtml::clean($answer), self::ANSWER_TAGS);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{type: string, id: int}>
     */
    private function attachments(array $data): array
    {
        return [
            ...array_map(fn (mixed $id): array => ['type' => (new Project)->getMorphClass(), 'id' => (int) $id], array_values(array_unique($data['project_ids'] ?? []))),
            ...array_map(fn (mixed $id): array => ['type' => (new Location)->getMorphClass(), 'id' => (int) $id], array_values(array_unique($data['location_ids'] ?? []))),
            ...array_map(fn (mixed $id): array => ['type' => (new FacetPage)->getMorphClass(), 'id' => (int) $id], array_values(array_unique($data['facet_ids'] ?? []))),
        ];
    }

    /**
     * @return list<array{type: string, id: int}>
     */
    private function currentAttachments(Faq $faq): array
    {
        return array_values(DB::table('faqables')->where('faq_id', $faq->id)->orderBy('id')
            ->get(['faqable_type', 'faqable_id'])
            ->map(fn (object $row): array => ['type' => (string) $row->faqable_type, 'id' => (int) $row->faqable_id])->all());
    }

    /**
     * @param  list<array{type: string, id: int}>  $attachments
     */
    private function syncAttachments(Faq $faq, array $attachments): void
    {
        DB::table('faqables')->where('faq_id', $faq->id)->delete();

        // A question's position on a page is its position in the global FAQ order, so the sort column starts at 0.
        DB::table('faqables')->insert(array_map(
            fn (array $attachment, int $position): array => [
                'faq_id' => $faq->id,
                'faqable_type' => $attachment['type'],
                'faqable_id' => $attachment['id'],
                'sort_order' => $position,
            ],
            $attachments,
            array_keys($attachments),
        ));
    }
}
