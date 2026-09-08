<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    #[Test]
    public function dashboard_help_section_maps_attendance_routes(): void
    {
        $this->assertEquals('attendance', dashboard_help_section_for_route('dashboard.attendance'));
        $this->assertEquals('attendance', dashboard_help_section_for_route('dashboard.attendance.index'));
        $this->assertEquals('attendance', dashboard_help_section_for_route('dashboard.staff-attendance'));
        $this->assertEquals('attendance', dashboard_help_section_for_route('dashboard.staff-attendance.index'));
    }

    #[Test]
    public function dashboard_help_section_maps_student_routes(): void
    {
        $this->assertEquals('managing_students', dashboard_help_section_for_route('dashboard.students'));
        $this->assertEquals('managing_students', dashboard_help_section_for_route('dashboard.students.index'));
        $this->assertEquals('managing_students', dashboard_help_section_for_route('dashboard.teachers'));
        $this->assertEquals('managing_students', dashboard_help_section_for_route('dashboard.bulk'));
    }

    #[Test]
    public function dashboard_help_section_maps_exam_routes(): void
    {
        $this->assertEquals('exams_results', dashboard_help_section_for_route('dashboard.exams'));
        $this->assertEquals('exams_results', dashboard_help_section_for_route('dashboard.results.index'));
        $this->assertEquals('exams_results', dashboard_help_section_for_route('dashboard.admit'));
        $this->assertEquals('exams_results', dashboard_help_section_for_route('dashboard.admit.index'));
        $this->assertEquals('exams_results', dashboard_help_section_for_route('dashboard.seat'));
        $this->assertEquals('exams_results', dashboard_help_section_for_route('dashboard.assignments.index'));
    }

    #[Test]
    public function dashboard_help_section_maps_finance_routes(): void
    {
        $this->assertEquals('fees_payments', dashboard_help_section_for_route('dashboard.fees'));
        $this->assertEquals('fees_payments', dashboard_help_section_for_route('dashboard.payments.index'));
        $this->assertEquals('fees_payments', dashboard_help_section_for_route('dashboard.expenses'));
        $this->assertEquals('fees_payments', dashboard_help_section_for_route('dashboard.budget'));
    }

    #[Test]
    public function dashboard_help_section_maps_cms_routes(): void
    {
        $this->assertEquals('cms_management', dashboard_help_section_for_route('dashboard.cms'));
        $this->assertEquals('cms_management', dashboard_help_section_for_route('dashboard.pages.index'));
        $this->assertEquals('cms_management', dashboard_help_section_for_route('dashboard.gallery'));
        $this->assertEquals('cms_management', dashboard_help_section_for_route('dashboard.announcements.index'));
        $this->assertEquals('cms_management', dashboard_help_section_for_route('dashboard.messages'));
    }

    #[Test]
    public function dashboard_help_section_maps_system_routes(): void
    {
        $this->assertEquals('public_website', dashboard_help_section_for_route('dashboard.help'));
        $this->assertEquals('public_website', dashboard_help_section_for_route('dashboard.backups'));
        $this->assertEquals('public_website', dashboard_help_section_for_route('dashboard.activity'));
        $this->assertEquals('public_website', dashboard_help_section_for_route('dashboard.settings.index'));
    }

    #[Test]
    public function dashboard_help_section_defaults_to_getting_started(): void
    {
        $this->assertEquals('getting_started', dashboard_help_section_for_route('dashboard'));
        $this->assertEquals('getting_started', dashboard_help_section_for_route('dashboard.dashboard'));
        $this->assertEquals('getting_started', dashboard_help_section_for_route(''));
        $this->assertEquals('getting_started', dashboard_help_section_for_route(null));
        $this->assertEquals('getting_started', dashboard_help_section_for_route('nonexistent.route'));
    }

    #[Test]
    public function site_ui_returns_default_when_key_not_found(): void
    {
        $this->assertEquals('default-value', site_ui('nonexistent.key', 'default-value'));
    }
}
