<?php

namespace Tests\Unit\Models;

use App\Models\LibrarySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibrarySettingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_key_columns(): void
    {
        $setting = LibrarySetting::create([
            'late_fee_per_day' => 10.00,
            'max_books_per_student' => 5,
            'max_books_per_teacher' => 15,
            'issue_duration_days' => 21,
        ]);

        $this->assertDatabaseHas('library_settings', [
            'id' => $setting->id,
            'late_fee_per_day' => 10.00,
            'max_books_per_student' => 5,
        ]);
        $this->assertSame('10.00', $setting->late_fee_per_day);
        $this->assertSame(5, $setting->max_books_per_student);
        $this->assertSame(15, $setting->max_books_per_teacher);
        $this->assertSame(21, $setting->issue_duration_days);
    }

    /** @test */
    public function it_uses_default_values_when_created_empty(): void
    {
        $setting = LibrarySetting::create()->fresh();

        $this->assertEquals('5.00', $setting->late_fee_per_day);
        $this->assertEquals(3, $setting->max_books_per_student);
        $this->assertEquals(10, $setting->max_books_per_teacher);
        $this->assertEquals(14, $setting->issue_duration_days);
    }

    /** @test */
    public function get_settings_returns_existing_record(): void
    {
        $setting = LibrarySetting::create([
            'late_fee_per_day' => 7.50,
            'max_books_per_student' => 4,
            'max_books_per_teacher' => 12,
            'issue_duration_days' => 10,
        ]);

        $fetched = LibrarySetting::getSettings();

        $this->assertEquals($setting->id, $fetched->id);
        $this->assertEquals('7.50', $fetched->late_fee_per_day);
    }

    /** @test */
    public function get_settings_creates_defaults_when_empty(): void
    {
        $fetched = LibrarySetting::getSettings();

        $this->assertNotNull($fetched->id);
        $this->assertEquals('5.00', $fetched->late_fee_per_day);
        $this->assertEquals(3, $fetched->max_books_per_student);
        $this->assertEquals(10, $fetched->max_books_per_teacher);
        $this->assertEquals(14, $fetched->issue_duration_days);
    }
}
