<?php

namespace App\Support\Publishing;

use App\Models\Contracts\PublishableEntity;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * CMS-PUB-006: the entity changed since the editor loaded it. Carries who and when for the dialog
 * "Changed by {user} at {time}" (Reload or Overwrite).
 */
final class StaleEntityException extends RuntimeException
{
    public function __construct(public readonly Model&PublishableEntity $entity)
    {
        parent::__construct('This item was changed by someone else after you opened it.');
    }

    /**
     * @return array{by: string|null, at: string|null, lock_version: int}
     */
    public function details(): array
    {
        $editor = $this->entity->getRelationValue('editor');

        return [
            'by' => $editor?->name,
            'at' => $this->entity->getAttribute('updated_at')?->toIso8601String(),
            'lock_version' => (int) $this->entity->getAttribute('lock_version'),
        ];
    }
}
