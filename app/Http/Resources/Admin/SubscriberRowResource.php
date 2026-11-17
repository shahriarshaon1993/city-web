<?php

namespace App\Http\Resources\Admin;

use App\Enums\SubscriberStatus;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the subscribers list (ADM-SCR-12).
 *
 * @mixin NewsletterSubscriber
 */
class SubscriberRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $request->user();

        return [
            'id' => $this->id,
            'email' => $this->email,
            'status' => $this->status->value,
            'status_label' => ucfirst($this->status->value),
            'source' => $this->source_url,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'can' => [
                'unsubscribe' => $this->status !== SubscriberStatus::Unsubscribed && $actor->can('update', $this->resource),
                'delete' => $actor->can('delete', $this->resource),
                'erase' => $actor->can('erase', $this->resource),
            ],
        ];
    }
}
