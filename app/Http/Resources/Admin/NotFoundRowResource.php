<?php

namespace App\Http\Resources\Admin;

use App\Models\NotFoundLog;
use App\Support\Redirects\RedirectPath;
use App\Support\Redirects\RuleSet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the 404 monitor (ADM-SCR-14). `redirect` is the active rule that now answers the
 * address, so a row turns "Redirected" the moment a redirect covers it.
 *
 * @mixin NotFoundLog
 */
class NotFoundRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rule = RuleSet::current()->match(RedirectPath::normalise($this->path));
        $manage = $request->user()->can('update', $this->resource);

        return [
            'id' => $this->id,
            'path' => $this->path,
            'referrer' => $this->referrer,
            'hits' => $this->hits,
            'first_seen_at' => $this->first_seen_at->toIso8601String(),
            'last_seen_at' => $this->last_seen_at->toIso8601String(),
            'is_ignored' => $this->is_ignored,
            'redirect' => $rule === null ? null : ['target' => $rule['target'], 'code' => $rule['code']],
            'test_url' => url($this->path),
            'can' => ['manage' => $manage],
        ];
    }
}
