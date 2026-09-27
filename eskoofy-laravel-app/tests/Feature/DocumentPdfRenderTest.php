<?php

namespace Tests\Feature;

use App\Models\DocumentDesign;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\WebsiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The marksheet templates are the only document views rendered by dompdf rather
 * than by the browser's print dialog, and they are the reason
 * DocumentDesignService emits literal values instead of CSS custom properties.
 *
 * These tests render the real views through the real renderer, so a change that
 * reintroduces `var()`/`calc()`/`:not()` in the design CSS fails here instead of
 * silently shipping a colour-less PDF.
 */
class DocumentPdfRenderTest extends TestCase
{
    use RefreshDatabase;

    private function settings(): WebsiteSetting
    {
        return WebsiteSetting::query()->create([
            'school_name' => 'Greenfield Academy',
            'established_year' => 2000,
            'address' => '1 Main Street',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'country' => 'Bangladesh',
            'postal_code' => '1207',
            'phone' => '+8801000000000',
            'email' => 'office@greenfield.test',
        ]);
    }

    private ?ExamResult $result = null;

    /** One exam/student per test, so the unique codes stay unique. */
    private function makeResult(): ExamResult
    {
        if ($this->result !== null) {
            return $this->result;
        }

        $class = SchoolClass::create(['name' => 'Class Ten']);
        $student = Student::create([
            'user_id' => \App\Models\User::factory()->create(['name' => 'Rina Haque'])->id,
            'class_id' => $class->id,
            'admission_number' => 'ADM-1001',
            'roll_number' => '7',
            'admission_date' => now()->subYears(2)->toDateString(),
            'first_name' => 'Rina',
            'last_name' => 'Haque',
        ]);

        $exam = Exam::create([
            'name' => 'Annual Examination 2026',
            'code' => 'ANNUAL-2026',
            'type' => Exam::TYPE_FINAL,
            'status' => Exam::STATUS_COMPLETED,
            'start_date' => now()->subDays(5),
            'end_date' => now()->subDay(),
            'total_marks' => 100,
            'passing_marks' => 33,
            'grading_type' => Exam::GRADING_GRADE,
        ]);

        return $this->result = ExamResult::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'obtained_marks' => 82,
            'grade' => 'A+',
            'grade_point' => 5.0,
            'status' => ExamResult::STATUS_PASSED,
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    #[Test]
    public function the_dashboard_marksheet_renders_through_dompdf(): void
    {
        $result = $this->makeResult();

        $html = view('dashboard.exams.marksheet-pdf', [
            'exam' => $result->exam,
            'result' => $result,
            'settings' => $this->settings(),
        ])->render();

        $this->assertStringContainsString('.doc-root.doc-marksheet{', $html);
        $this->assertDomPdfSafe($html);

        $output = Pdf::loadHTML($html)->output();

        $this->assertStringStartsWith('%PDF-', $output);
        $this->assertGreaterThan(1000, strlen($output), 'a blank/failed render still produces a small file');
    }

    #[Test]
    public function the_public_results_marksheet_renders_through_dompdf(): void
    {
        $result = $this->makeResult();

        // Matches SiteResultController@downloadPdf: a collection named $result.
        $html = view('site.results-pdf', [
            'student' => $result->student,
            'result' => collect([$result]),
            'settings' => $this->settings(),
        ])->render();

        $this->assertStringContainsString('.doc-root.doc-marksheet{', $html);
        $this->assertDomPdfSafe($html);

        $this->assertStringStartsWith('%PDF-', Pdf::loadHTML($html)->output());
    }

    #[Test]
    public function an_enabled_watermark_survives_the_render(): void
    {
        DocumentDesign::query()->create([
            'document_type' => 'marksheet',
            'name' => 'Watermarked',
            'template' => 'bordered',
            'settings' => ['primary_color' => '#7c3aed'],
            'watermark' => [
                'enabled' => true,
                'type' => 'text',
                'text' => 'CONFIDENTIAL',
                'opacity' => 0.2,
                'position' => 'diagonal',
                'rotation' => 45,
            ],
            'is_default' => true,
            'is_active' => true,
        ]);
        \App\Services\DocumentDesignService::flushCache();

        $result = $this->makeResult();
        $html = view('dashboard.exams.marksheet-pdf', [
            'exam' => $result->exam,
            'result' => $result,
            'settings' => $this->settings(),
        ])->render();

        $this->assertStringContainsString('class="doc-watermark"', $html);
        $this->assertStringContainsString('CONFIDENTIAL', $html);
        $this->assertStringContainsString('rotate(45deg)', $html);
        $this->assertStringContainsString('color:#7c3aed', $html, 'the design colour must be a literal');

        $this->assertStringStartsWith('%PDF-', Pdf::loadHTML($html)->output());
    }

    #[Test]
    public function a_disabled_watermark_renders_no_watermark_markup(): void
    {
        $result = $this->makeResult();
        $html = view('dashboard.exams.marksheet-pdf', [
            'exam' => $result->exam,
            'result' => $result,
            'settings' => $this->settings(),
        ])->render();

        $this->assertStringNotContainsString('doc-watermark', $html);
    }

    /**
     * The properties in this list are silently dropped by dompdf, so a design
     * that relies on them renders as unstyled text rather than failing loudly.
     */
    private function assertDomPdfSafe(string $html): void
    {
        foreach (['var(--', 'calc(', 'color-mix(', ':not('] as $unsupported) {
            $this->assertStringNotContainsString($unsupported, $html, "dompdf does not support {$unsupported}");
        }
    }
}
