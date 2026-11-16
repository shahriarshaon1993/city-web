<?php

namespace App\Enums;

/** DM-FACET `kind`: the two facets that have their own landing page (`/projects/type/…`, `/projects/status/…`). */
enum FacetKind: string
{
    case Segment = 'segment';
    case Status = 'status';
}
