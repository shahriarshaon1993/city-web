<?php

namespace App\Enums;

/** An enum whose cases have a plain-words label for the admin (filters, selects, tables). */
interface Labelled
{
    public function label(): string;
}
