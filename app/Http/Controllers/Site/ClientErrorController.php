<?php

namespace App\Http\Controllers\Site;

use App\Actions\Monitoring\LogClientError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreClientErrorRequest;
use Illuminate\Http\Response;

/**
 * `/internal/client-errors` (SEO-ANA-006): rate-limited 10/min/IP, sent
 * with `navigator.sendBeacon`, so it is excluded from CSRF checks.
 */
class ClientErrorController extends Controller
{
    public function __invoke(StoreClientErrorRequest $request, LogClientError $logClientError): Response
    {
        $logClientError->handle(
            source: $request->string('source')->toString(),
            message: $request->string('message')->toString(),
            url: $request->input('url'),
            userAgent: $request->userAgent(),
        );

        return response()->noContent();
    }
}
