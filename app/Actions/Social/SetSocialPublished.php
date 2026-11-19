<?php

namespace App\Actions\Social;

use App\Actions\Activity\LogActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The inline Published switch of the testimonials, partners, FAQs and team lists (CRUD-01). A
 * switched-off item disappears from the website at once; the switch is not a draft/publish
 * workflow (crud.md matrix: "Published toggle").
 */
final class SetSocialPublished
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, Model $item, bool $published, string $log, string $label): Model
    {
        if ((bool) $item->getAttribute('is_published') === $published) {
            return $item;
        }

        $item->forceFill(['is_published' => $published])->save();

        $this->logActivity->handle($log, 'updated', ($published ? 'Published: ' : 'Unpublished: ').$label, $actor, $item, ['label' => $label, 'published' => $published]);

        return $item;
    }
}
