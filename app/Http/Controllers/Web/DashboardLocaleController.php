<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class DashboardLocaleController extends Controller
{
    public function switch(string $locale): RedirectResponse
    {
        if (!in_array($locale, (array) config('school.supported_locales', ['en']))) {
            abort(400);
        }

        session(['dashboard_locale' => $locale]);
        app()->setLocale($locale);

        return redirect()->to(request()->header('referer', route('dashboard')))->with('status', __('Language changed.'));
    }
}
