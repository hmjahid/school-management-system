<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function switch(string $locale): RedirectResponse
    {
        $locales = config('school.supported_locales', ['en']);

        if (in_array($locale, $locales, true)) {
            session(['locale' => $locale]);
        }

        return redirect()->back();
    }
}
