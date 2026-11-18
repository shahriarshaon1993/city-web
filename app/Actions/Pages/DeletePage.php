<?php

namespace App\Actions\Pages;

use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Models\Page;
use App\Models\RecycleBinEntry;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * DEL-01 / DEL-03: Delete page = Recycle bin. A system page is part of the website and is refused
 * with its sentence (`Page::binUsageBlock`). Restoring brings it back as a Draft (DEL-05).
 */
final class DeletePage
{
    public function __construct(private readonly MoveToRecycleBin $moveToRecycleBin) {}

    /**
     * @throws ValidationException when the page is a system page
     */
    public function handle(User $actor, Page $page): RecycleBinEntry
    {
        return $this->moveToRecycleBin->handle($actor, $page);
    }
}
