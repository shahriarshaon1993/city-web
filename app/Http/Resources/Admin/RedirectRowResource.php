<?php

namespace App\Http\Resources\Admin;

use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the redirects list (ADM-SCR-14). `can` says what the signed-in user may do (ARC-005);
 * the Policy re-checks on the server.
 *
 * @mixin Redirect
 */
class RedirectRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $manage = $request->user()->can('update', $this->resource);

        return [
            'id' => $this->id,
            'source' => $this->sourceLabel(),
            'target' => $this->target,
            'code' => $this->code,
            'is_prefix' => $this->is_prefix,
            'is_active' => $this->is_active,
            'hits' => $this->hits,
            'last_hit_at' => $this->last_hit_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'created_by' => $this->creator?->name,
            // HELP-04: a link to try the rule (a prefix rule has no single address to open).
            'test_url' => $this->is_prefix ? null : url($this->source),
            'can' => ['edit' => $manage, 'delete' => $manage],
        ];
    }
}
