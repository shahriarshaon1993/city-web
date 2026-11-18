<?php

namespace App\Actions\Posts;

use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Models\Post;
use App\Models\RecycleBinEntry;
use App\Models\User;

/**
 * DEL-01: Delete post = Recycle bin with Undo. The article leaves the website at once; restoring brings
 * it back as a Draft (DEL-05), and its old address answers 404 until it is published again.
 */
final class DeletePost
{
    public function __construct(private readonly MoveToRecycleBin $moveToRecycleBin) {}

    public function handle(User $actor, Post $post): RecycleBinEntry
    {
        return $this->moveToRecycleBin->handle($actor, $post);
    }
}
