<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Redirects\RobotsTxt;
use Illuminate\Http\Response;

/**
 * SEO-TEC-010: robots.txt from Settings, never a static file. Outside production it always
 * disallows everything.
 */
class RobotsTxtController extends Controller
{
    public function __invoke(): Response
    {
        return response(RobotsTxt::served(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
