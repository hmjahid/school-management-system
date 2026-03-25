<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\WebsiteContent;
use Illuminate\View\View;

class SiteGalleryController extends Controller
{
    public function index(): View
    {
        $content = WebsiteContent::getContent('gallery');

        $items = Gallery::query()
            ->published()
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn ($g) => $g->category ?: __('General'));

        return view('site.gallery', compact('content', 'items'));
    }
}
