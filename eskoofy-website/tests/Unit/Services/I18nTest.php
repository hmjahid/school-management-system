<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\I18n;
use Tests\TestCase;

class I18nTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        \App\Core\Session::getInstance()->remove('locale');
    }

    public function test_default_locale_is_en(): void
    {
        $this->assertSame('en', I18n::current());
    }

    public function test_english_translation_resolves(): void
    {
        $this->assertSame('International · USD pricing · SaaS', I18n::t('home.badge', [], 'en'));
    }

    public function test_bangla_translation_resolves(): void
    {
        $this->assertSame('আন্তর্জাতিক · ইউএসডি মূল্য · সাস', I18n::t('home.badge', [], 'bn'));
    }

    public function test_unknown_key_falls_back_to_key(): void
    {
        $this->assertSame('nope.not_a_key', I18n::t('nope.not_a_key', [], 'en'));
    }

    public function test_placeholders_are_replaced(): void
    {
        $this->assertSame('Hello Eskoofy!', I18n::t('greeting.hello', ['name' => 'Eskoofy'], 'en'));
    }

    public function test_switch_stores_locale_in_session(): void
    {
        $this->assertTrue(I18n::switch('bn'));
        $this->assertSame('bn', I18n::current());

        $this->assertFalse(I18n::switch('xx'));
        $this->assertSame('bn', I18n::current());
    }

    public function test_supported_locales_include_en_and_bn(): void
    {
        $locales = I18n::supported();

        $this->assertArrayHasKey('en', $locales);
        $this->assertArrayHasKey('bn', $locales);
    }

    public function test_every_en_key_has_bangla_translation(): void
    {
        $en = require dirname(__DIR__, 3) . '/lang/en.php';
        $bn = require dirname(__DIR__, 3) . '/lang/bn.php';

        $missing = array_diff(array_keys($en), array_keys($bn));
        $this->assertSame([], $missing, 'Missing bn translations: ' . implode(', ', $missing));
    }
}