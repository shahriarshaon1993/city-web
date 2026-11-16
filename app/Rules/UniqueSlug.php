<?php

namespace App\Rules;

use App\Models\Location;
use App\Models\Project;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * VAL-01 / DEL-08: a slug is unique across the table, the Recycle bin included. When the holder is
 * in the bin the message says so, so the editor can restore it or delete it forever.
 */
final class UniqueSlug implements ValidationRule
{
    /**
     * @param  class-string<Project|Location>  $model
     */
    public function __construct(private readonly string $model, private readonly ?int $ignoreId = null, private readonly string $noun = 'item') {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $holder = $this->model::withTrashed()
            ->where('slug', (string) $value)
            ->when($this->ignoreId !== null, fn ($query) => $query->whereKeyNot($this->ignoreId))
            ->first();

        if ($holder === null) {
            return;
        }

        $fail($holder->trashed()
            ? __('This URL belongs to “:name” in the Recycle bin — restore it or delete it forever.', ['name' => $holder->binLabel()])
            : __('This URL is already used by another :noun.', ['noun' => $this->noun]));
    }
}
