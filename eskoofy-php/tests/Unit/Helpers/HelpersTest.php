<?php
declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Core\Session;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_esc_html_encodes(): void
    {
        $this->assertSame('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', esc('<script>alert("xss")</script>'));
    }

    public function test_esc_passes_clean_string(): void
    {
        $this->assertSame('hello world', esc('hello world'));
    }

    public function test_e_null_returns_empty(): void
    {
        $this->assertSame('', e(null));
    }

    public function test_e_encodes(): void
    {
        $this->assertSame('5 &gt; 3', e('5 > 3'));
    }

    public function test_old_returns_stored_value(): void
    {
        Session::getInstance()->set('_old_name', 'John');
        $this->assertSame('John', old('name'));
    }

    public function test_old_returns_default_when_missing(): void
    {
        $this->assertNull(old('nonexistent'));
        $this->assertSame('fallback', old('nonexistent', 'fallback'));
    }

    public function test_csrf_field_returns_hidden_input(): void
    {
        $_SESSION['csrf_token'] = 'test_token_123';
        $field = csrf_field();
        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="_token"', $field);
        $this->assertStringContainsString('value="test_token_123"', $field);
    }

    public function test_csrf_token_returns_token(): void
    {
        $_SESSION['csrf_token'] = 'abc';
        $this->assertSame('abc', csrf_token());
    }

    public function test_url_builds_correctly(): void
    {
        $result = url('students');
        $this->assertStringContainsString('/students', $result);
    }

    public function test_url_strips_leading_slash(): void
    {
        $result = url('/students');
        $this->assertStringContainsString('/students', $result);
        $this->assertStringNotContainsString('//students', $result);
    }

    public function test_asset_builds_correctly(): void
    {
        $result = asset('css/app.css');
        $this->assertStringContainsString('/assets/css/app.css', $result);
    }

    public function test_now_returns_datetime_string(): void
    {
        $result = now();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    public function test_today_returns_date_string(): void
    {
        $result = today();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $result);
    }

    public function test_format_currency_bdt(): void
    {
        $result = format_currency(1234.5);
        $this->assertStringContainsString('1,234.50', $result);
    }

    public function test_format_currency_usd(): void
    {
        $result = format_currency(99.9, 'USD');
        $this->assertStringContainsString('99.90', $result);
    }

    public function test_format_currency_eur(): void
    {
        $result = format_currency(50, 'EUR');
        $this->assertStringContainsString('50.00', $result);
    }

    public function test_format_currency_gbp(): void
    {
        $result = format_currency(25.5, 'GBP');
        $this->assertStringContainsString('25.50', $result);
    }

    public function test_format_currency_unknown_uses_code(): void
    {
        $result = format_currency(10, 'JPY');
        $this->assertStringContainsString('10.00', $result);
    }

    public function test_generate_invoice_number_format(): void
    {
        $result = generate_invoice_number();
        $this->assertMatchesRegularExpression('/^INV-\d{8}-\d{5}$/', $result);
    }

    public function test_generate_invoice_number_custom_prefix(): void
    {
        $result = generate_invoice_number('FEE');
        $this->assertStringStartsWith('FEE-', $result);
    }

    public function test_generate_admission_number_format(): void
    {
        $result = generate_admission_number();
        $this->assertMatchesRegularExpression('/^ADM-\d{4}-\d{5}$/', $result);
    }

    public function test_generate_application_number_format(): void
    {
        $result = generate_application_number();
        $this->assertMatchesRegularExpression('/^APP-\d{4}-\d{5}$/', $result);
    }

    public function test_ms_unit_label(): void
    {
        $this->assertSame('500ms', ms_unit_label(500));
        $this->assertSame('1.5s', ms_unit_label(1500));
        $this->assertSame('2.5m', ms_unit_label(150000));
        $this->assertSame('1.5h', ms_unit_label(5400000));
    }

    public function test_slugify(): void
    {
        $this->assertSame('hello-world', slugify('Hello World'));
        $this->assertSame('this-is-a-test', slugify('This Is A Test'));
        $this->assertSame('special-chars', slugify('Special!@#$%Chars'));
        $this->assertSame('multiple-dashes', slugify('Multiple   ---   Dashes'));
    }

    public function test_truncate(): void
    {
        $this->assertSame('Hello', truncate('Hello', 10));
        $this->assertSame('Hello...', truncate('Hello World This Is Long', 5));
        $this->assertSame('exact', truncate('exact', 5));
    }

    public function test_dashboard_help_section_for_route(): void
    {
        $this->assertSame('overview', dashboard_help_section_for_route('dashboard'));
        $this->assertSame('students', dashboard_help_section_for_route('students'));
        $this->assertSame('teachers', dashboard_help_section_for_route('teachers'));
        $this->assertSame('academics', dashboard_help_section_for_route('classes'));
        $this->assertSame('exams', dashboard_help_section_for_route('exams'));
        $this->assertSame('fees', dashboard_help_section_for_route('fees'));
        $this->assertSame('attendance', dashboard_help_section_for_route('attendance'));
        $this->assertSame('admissions', dashboard_help_section_for_route('admissions'));
        $this->assertSame('payments', dashboard_help_section_for_route('payments'));
        $this->assertSame('reports', dashboard_help_section_for_route('reports'));
        $this->assertSame('settings', dashboard_help_section_for_route('settings'));
    }

    public function test_dashboard_help_section_for_unknown_route(): void
    {
        $this->assertNull(dashboard_help_section_for_route('unknown-page'));
    }

    public function test_flash_stores_in_session(): void
    {
        flash('success');
        Session::getInstance()->flash('success', 'It worked');
        $this->assertSame('It worked', Session::getInstance()->getFlash('success'));
    }

    public function test_flash_all_returns_all_flashes(): void
    {
        Session::getInstance()->flash('a', 1);
        Session::getInstance()->flash('b', 2);
        $result = flash_all();
        $this->assertCount(2, $result);
    }

    public function test_config_reads_app_config(): void
    {
        $name = config('name');
        $this->assertIsString($name);
    }

    public function test_config_returns_default_on_missing(): void
    {
        $this->assertSame('fallback', config('nonexistent.key', 'fallback'));
    }

    public function test_config_dot_notation(): void
    {
        $schoolName = config('school.name');
        $this->assertIsString($schoolName);
    }

    public function test_auth_helper(): void
    {
        $auth = auth();
        $this->assertFalse($auth->check());
        $this->assertNull($auth->id());
        $this->assertNull($auth->role());
    }
}
