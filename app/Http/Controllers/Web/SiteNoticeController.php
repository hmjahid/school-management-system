<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\WebsiteSetting;
use Illuminate\Http\Request;

class SiteNoticeController extends Controller
{
    public function index(Request $request)
    {
        $siteSettings = WebsiteSetting::getSettings();
        $notices = Notice::query()
            ->orderByDesc('is_urgent')
            ->orderByDesc('pinned')
            ->orderByDesc('id')
            ->paginate(15);

        return view('site.notices', compact('siteSettings', 'notices'));
    }
}
