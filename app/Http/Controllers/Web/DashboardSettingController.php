<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LibrarySetting;
use App\Models\WebsiteContent;
use App\Models\WebsiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardSettingController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('manage_school_settings'), 403);

        $settings = WebsiteSetting::getSettings();

        return view('dashboard.settings.general', compact('settings'));
    }

    public function general(): View
    {
        abort_unless(auth()->user()?->can('manage_school_settings'), 403);

        $settings = WebsiteSetting::getSettings();
        $librarySettings = LibrarySetting::getSettings();
        $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);

        return view('dashboard.settings.index', compact('settings', 'librarySettings', 'timezones'));
    }

    public function cmsSettings(): View
    {
        abort_unless(auth()->user()?->can('manage_school_settings'), 403);

        $settings = WebsiteSetting::getSettings();

        return view('dashboard.settings.cms', compact('settings'));
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_school_settings'), 403);

        foreach (['website', 'facebook_url', 'twitter_url', 'instagram_url', 'linkedin_url', 'youtube_url'] as $urlKey) {
            if ($request->input($urlKey) === '') {
                $request->merge([$urlKey => null]);
            }
        }

        $validated = $request->validate([
            'school_name' => ['nullable', 'string', 'max:255'],
            'school_name_bn' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:500'],
            'tagline_bn' => ['nullable', 'string', 'max:500'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'default_locale' => ['nullable', 'string', 'in:'.implode(',', (array) config('school.supported_locales', ['en']))],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'favicon' => ['nullable', 'file', 'max:512', 'mimes:ico,png,jpg,jpeg,gif,webp,svg'],
            'remove_favicon' => ['nullable', 'boolean'],
            'footer_logo' => ['nullable', 'image', 'max:2048'],
            'remove_footer_logo' => ['nullable', 'boolean'],
            'footer_logo_dark' => ['nullable', 'image', 'max:2048'],
            'remove_footer_logo_dark' => ['nullable', 'boolean'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'show_facebook' => ['nullable', 'boolean'],
            'twitter_url' => ['nullable', 'url', 'max:255'],
            'show_twitter' => ['nullable', 'boolean'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'show_instagram' => ['nullable', 'boolean'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'show_linkedin' => ['nullable', 'boolean'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'show_youtube' => ['nullable', 'boolean'],
        ]);

        $settings = WebsiteSetting::firstOrNew([]);

        if ($request->boolean('remove_logo') && $settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
            $settings->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $settings->logo_path = $request->file('logo')->store('website', 'public');
        }

        if ($request->boolean('remove_footer_logo') && $settings->footer_logo_path) {
            Storage::disk('public')->delete($settings->footer_logo_path);
            $settings->footer_logo_path = null;
        }

        if ($request->hasFile('footer_logo')) {
            if ($settings->footer_logo_path) {
                Storage::disk('public')->delete($settings->footer_logo_path);
            }
            $settings->footer_logo_path = $request->file('footer_logo')->store('website', 'public');
        }

        if ($request->boolean('remove_footer_logo_dark') && $settings->footer_logo_dark_path) {
            Storage::disk('public')->delete($settings->footer_logo_dark_path);
            $settings->footer_logo_dark_path = null;
        }

        if ($request->hasFile('footer_logo_dark')) {
            if ($settings->footer_logo_dark_path) {
                Storage::disk('public')->delete($settings->footer_logo_dark_path);
            }
            $settings->footer_logo_dark_path = $request->file('footer_logo_dark')->store('website', 'public');
        }

        if ($request->boolean('remove_favicon') && $settings->favicon_path) {
            Storage::disk('public')->delete($settings->favicon_path);
            $settings->favicon_path = null;
        }

        if ($request->hasFile('favicon')) {
            if ($settings->favicon_path) {
                Storage::disk('public')->delete($settings->favicon_path);
            }
            $settings->favicon_path = $request->file('favicon')->store('website', 'public');
        }

        unset($validated['logo'], $validated['remove_logo'], $validated['favicon'], $validated['remove_favicon'], $validated['footer_logo'], $validated['remove_footer_logo'], $validated['footer_logo_dark'], $validated['remove_footer_logo_dark']);

        $settings->fill($validated);
        $settings->save();

        return redirect()->route('dashboard.settings.index')->with('status', __('Settings saved.'));
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_school_settings'), 403);

        $validated = $request->validate([
            'theme_primary_color' => ['nullable', 'string', 'max:20'],
            'theme_secondary_color' => ['nullable', 'string', 'max:20'],
            'theme_font_family' => ['nullable', 'string', 'max:100'],
            'theme_border_radius' => ['nullable', 'string', 'max:20'],
        ]);

        $settings = WebsiteSetting::firstOrNew([]);
        $settings->fill($validated);
        $settings->save();

        return redirect()->route('dashboard.settings.index', ['tab' => 'theme'])->with('status', __('Theme settings saved.'));
    }

    public function updateLocalization(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_school_settings'), 403);

        $validated = $request->validate([
            'timezone' => ['nullable', 'string', 'max:64'],
            'date_format' => ['nullable', 'string', 'max:20'],
            'time_format' => ['nullable', 'string', 'max:20'],
            'default_locale' => ['nullable', 'string', 'in:'.implode(',', (array) config('school.supported_locales', ['en']))],
        ]);

        $settings = WebsiteSetting::firstOrNew([]);
        $settings->fill($validated);
        $settings->save();

        return redirect()->route('dashboard.settings.index', ['tab' => 'localization'])->with('status', __('Localization settings saved.'));
    }

    public function updatePayment(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_school_settings'), 403);

        $validated = $request->validate([
            'bkash_merchant_number' => ['nullable', 'string', 'max:50'],
            'bkash_api_key' => ['nullable', 'string', 'max:255'],
            'bkash_api_secret' => ['nullable', 'string', 'max:255'],
            'bkash_username' => ['nullable', 'string', 'max:255'],
            'bkash_password' => ['nullable', 'string', 'max:255'],
            'bkash_app_key' => ['nullable', 'string', 'max:255'],
            'bkash_app_secret' => ['nullable', 'string', 'max:255'],
            'bkash_sandbox' => ['nullable', 'boolean'],
            'nagad_merchant_number' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'max:10'],
            'default_payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        $settings = WebsiteSetting::firstOrNew([]);
        $settings->fill($validated);

        if ($request->has('bkash_sandbox')) {
            $settings->bkash_sandbox = $request->boolean('bkash_sandbox');
        }

        $settings->save();

        return redirect()->route('dashboard.settings.index', ['tab' => 'payment'])->with('status', __('Payment settings saved.'));
    }

    public function updateLibrary(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_school_settings'), 403);

        $validated = $request->validate([
            'late_fee_per_day' => ['nullable', 'numeric', 'min:0'],
            'max_books_per_student' => ['nullable', 'integer', 'min:1'],
            'max_books_per_teacher' => ['nullable', 'integer', 'min:1'],
            'issue_duration_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $librarySettings = LibrarySetting::firstOrNew([]);
        $librarySettings->fill($validated);
        $librarySettings->save();

        return redirect()->route('dashboard.settings.index', ['tab' => 'library'])->with('status', __('Library settings saved.'));
    }

    public function globalLabels(): View
    {
        abort_unless(auth()->user()?->can('manage_school_settings'), 403);

        $defaults = \App\Support\SiteFrontend::defaultsForLocale();
        $row = WebsiteContent::query()->where('page', 'site-ui')->first();
        $overrides = $row ? $row->englishContentTree() : [];

        $flattened = $this->flattenLabels($defaults, $overrides);

        return view('dashboard.settings.global-labels', compact('flattened'));
    }

    public function updateGlobalLabels(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_school_settings'), 403);

        $data = $request->input('labels', []);

        $row = WebsiteContent::query()->firstOrCreate(
            ['page' => 'site-ui'],
            ['is_active' => true],
        );

        $defaults = \App\Support\SiteFrontend::defaultsForLocale();
        $listPaths = $this->listPaths($defaults);

        foreach (['en', 'bn'] as $locale) {
            if (!isset($data[$locale])) continue;
            $data[$locale] = $this->normalizeListValues($data[$locale], $listPaths, '');
        }

        $row->content_en = $data['en'] ?? [];
        $row->content_bn = $data['bn'] ?? [];
        $row->is_active = true;
        $row->save();

        return redirect()->route('dashboard.settings.global-labels')->with('status', __('Global labels saved.'));
    }

    private function listPaths(array $defaults, string $prefix = ''): array
    {
        $paths = [];
        foreach ($defaults as $key => $val) {
            $path = $prefix ? "{$prefix}.{$key}" : $key;
            if ($this->isSimpleList($val)) {
                $paths[] = $path;
            } elseif (is_array($val)) {
                $paths = array_merge($paths, $this->listPaths($val, $path));
            }
        }
        return $paths;
    }

    private function normalizeListValues(array $data, array $listPaths, string $prefix): array
    {
        $result = [];
        foreach ($data as $key => $val) {
            $path = $prefix ? "{$prefix}.{$key}" : $key;
            if (in_array($path, $listPaths)) {
                $result[$key] = is_string($val) ? array_filter(array_map('trim', explode("\n", $val)), fn($v) => $v !== '') : $val;
            } elseif (is_array($val)) {
                $result[$key] = $this->normalizeListValues($val, $listPaths, $path);
            } else {
                $result[$key] = $val;
            }
        }
        return $result;
    }

    private function flattenLabels(array $defaults, array $overrides, string $prefix = ''): array
    {
        $result = [];
        foreach ($defaults as $key => $val) {
            $path = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_string($val)) {
                $parts = explode('.', $path);
                $section = $parts[0];
                $label = implode('.', array_slice($parts, 1)) ?: $key;
                $enDefault = $val;
                $enOverride = Arr::get($overrides, $path);
                $isList = false;
                $result[] = compact('section', 'label', 'path', 'enDefault', 'enOverride', 'isList');
            } elseif ($this->isSimpleList($val)) {
                $parts = explode('.', $path);
                $section = $parts[0];
                $label = implode('.', array_slice($parts, 1)) ?: $key;
                $enDefault = '';
                $isList = true;
                $listDefaults = $val;
                $enOverride = Arr::get($overrides, $path);
                $result[] = compact('section', 'label', 'path', 'enDefault', 'enOverride', 'isList', 'listDefaults');
            } elseif (is_array($val)) {
                $result = array_merge($result, $this->flattenLabels($val, $overrides, $path));
            }
        }
        return $result;
    }

    private function isSimpleList($val): bool
    {
        if (!is_array($val)) return false;
        if ($val === []) return true;
        foreach ($val as $k => $v) {
            if (!is_int($k)) return false;
            if (is_array($v)) {
                if (!empty($v)) return false;
            }
        }
        return true;
    }
}
