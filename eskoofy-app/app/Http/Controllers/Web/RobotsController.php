<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function index(): Response
    {
        $sitemap = route('site.sitemap');

        $body = <<<ROBOTS
User-agent: *
Allow: /

Sitemap: {$sitemap}
ROBOTS;

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }
}
