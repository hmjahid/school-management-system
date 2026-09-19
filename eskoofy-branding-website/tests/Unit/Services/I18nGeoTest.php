<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Core\Model;
use App\Services\I18n;
use Tests\TestCase;

class I18nGeoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \App\Core\Session::getInstance()->remove('locale');
        \App\Core\Session::getInstance()->remove('geo_locale');
    }

    protected function tearDown(): void
    {
        \App\Core\Session::getInstance()->remove('locale');
        \App\Core\Session::getInstance()->remove('geo_locale');
        parent::tearDown();
    }

    public function test_default_locale_is_en(): void
    {
        $this->assertSame('en', I18n::current());
    }

    public function test_manual_locale_wins_over_geo_locale(): void
    {
        \App\Core\Session::getInstance()->set('geo_locale', 'en');
        \App\Core\Session::getInstance()->set('locale', 'bn');

        $this->assertSame('bn', I18n::current());
    }

    public function test_geo_locale_wins_over_default(): void
    {
        \App\Core\Session::getInstance()->set('geo_locale', 'bn');

        $this->assertSame('bn', I18n::current());
    }

    public function test_unsupported_geo_locale_falls_back_to_default(): void
    {
        \App\Core\Session::getInstance()->set('geo_locale', 'xx');

        $this->assertSame('en', I18n::current());
    }

    public function test_wants_timezone_hint_is_false_for_bd_locale(): void
    {
        \App\Core\Session::getInstance()->set('geo_locale', 'bn');
        \App\Core\Session::getInstance()->remove('geo_tz_checked');

        $this->assertFalse(I18n::wantsTimezoneHint());
    }

    public function test_wants_timezone_hint_is_true_for_en_when_unchecked(): void
    {
        \App\Core\Session::getInstance()->set('geo_locale', 'en');
        \App\Core\Session::getInstance()->remove('geo_tz_checked');

        $this->assertTrue(I18n::wantsTimezoneHint());
    }

    public function test_wants_timezone_hint_is_false_after_tz_check(): void
    {
        \App\Core\Session::getInstance()->set('geo_locale', 'en');
        \App\Core\Session::getInstance()->set('geo_tz_checked', true);

        $this->assertFalse(I18n::wantsTimezoneHint());
    }

    public function test_wants_timezone_hint_is_false_when_manual_locale_is_set(): void
    {
        \App\Core\Session::getInstance()->set('locale', 'bn');
        \App\Core\Session::getInstance()->remove('geo_tz_checked');

        $this->assertFalse(I18n::wantsTimezoneHint());
    }
}