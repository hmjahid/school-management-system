<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\PaymentGateway;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Schema;

class SetupChecklistService
{
    /**
     * Ordered core setup tasks shown in the onboarding wizard + dashboard checklist.
     *
     * @return array<int, array{key: string, label: string, description: string, url: string, done: bool}>
     */
    public function items(): array
    {
        $settings = $this->safe(fn () => WebsiteSetting::getSettings());

        $items = [
            'school_info' => [
                'key' => 'school_info',
                'label' => __('dashboard.setup.school_info'),
                'description' => __('dashboard.setup.school_info_desc'),
                'url' => route('dashboard.settings.general'),
                'done' => filled($settings ?? null) && filled($settings->school_name ?? null),
            ],
            'timezone' => [
                'key' => 'timezone',
                'label' => __('dashboard.setup.timezone'),
                'description' => __('dashboard.setup.timezone_desc'),
                'url' => route('dashboard.settings.general'),
                'done' => $this->timezoneConfigured($settings),
            ],
            'academic_session' => [
                'key' => 'academic_session',
                'label' => __('dashboard.setup.academic_session'),
                'description' => __('dashboard.setup.academic_session_desc'),
                'url' => route('dashboard.classes'),
                'done' => $this->safe(fn () => Schema::hasTable('academic_sessions') && AcademicSession::query()->count() > 0) ?? false,
            ],
            'classes' => [
                'key' => 'classes',
                'label' => __('dashboard.setup.classes'),
                'description' => __('dashboard.setup.classes_desc'),
                'url' => route('dashboard.classes'),
                'done' => $this->safe(fn () => Schema::hasTable('school_classes') && SchoolClass::query()->count() > 0) ?? false,
            ],
            'teachers' => [
                'key' => 'teachers',
                'label' => __('dashboard.setup.teachers'),
                'description' => __('dashboard.setup.teachers_desc'),
                'url' => route('dashboard.teachers'),
                'done' => $this->safe(fn () => Schema::hasTable('users') && User::role('teacher')->count() > 0) ?? false,
            ],
            'payment' => [
                'key' => 'payment',
                'label' => __('dashboard.setup.payment'),
                'description' => __('dashboard.setup.payment_desc'),
                'url' => route('dashboard.settings.index'),
                'done' => $this->safe(fn () => Schema::hasTable('payment_gateways') && PaymentGateway::query()->active()->count() > 0) ?? false,
            ],
        ];

        return $items;
    }

    public function isComplete(): bool
    {
        foreach ($this->items() as $item) {
            if (! $item['done']) {
                return false;
            }
        }

        return true;
    }

    public function completionPercent(): int
    {
        $done = collect($this->items())->filter(fn ($item) => $item['done'])->count();
        $total = count($this->items());

        return $total > 0 ? (int) round(100 * $done / $total) : 100;
    }

    /**
     * Run a callback defensively so a missing table or role never breaks setup pages.
     */
    private function safe(\Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (\Throwable) {
            return null;
        }
    }

    private function timezoneConfigured(mixed $settings): bool
    {
        if (! $settings || blank($settings->timezone ?? null)) {
            return false;
        }

        try {
            new \DateTimeZone((string) $settings->timezone);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
