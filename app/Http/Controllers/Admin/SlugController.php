<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CheckSlugRequest;
use App\Support\Slugs\SlugAvailability;
use Illuminate\Http\JsonResponse;

/**
 * VAL-04: answers the slug availability check of every slug field (✓ / ✕ + reason).
 */
class SlugController extends Controller
{
    public function __invoke(CheckSlugRequest $request, SlugAvailability $availability): JsonResponse
    {
        return response()->json($availability->check(
            $request->string('source')->toString(),
            $request->string('slug')->toString(),
            $request->filled('ignore') ? $request->integer('ignore') : null,
        ));
    }
}
