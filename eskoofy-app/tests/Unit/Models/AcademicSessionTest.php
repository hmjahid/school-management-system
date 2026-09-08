<?php

namespace Tests\Unit\Models;

use App\Models\AcademicSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AcademicSessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_persists_fillable_columns(): void
    {
        $code = 'CODE'.uniqid();
        $name = 'Session '.uniqid();
        $start = '2024-01-01';
        $end = '2024-12-31';

        $session = AcademicSession::create([
            'name' => $name,
            'code' => $code,
            'start_date' => $start,
            'end_date' => $end,
            'description' => 'Annual session',
            'is_active' => true,
            'is_current' => true,
            'status' => 'active',
            'metadata' => ['key' => 'value'],
        ]);

        $this->assertDatabaseHas('academic_sessions', [
            'name' => $name,
            'code' => $code,
            'is_active' => true,
            'is_current' => true,
            'status' => 'active',
            'description' => 'Annual session',
        ]);

        $this->assertSame($name, $session->name);
        $this->assertSame($code, $session->code);
    }

    #[Test]
    public function it_casts_dates_boolean_and_metadata(): void
    {
        $session = AcademicSession::create([
            'name' => 'Session '.uniqid(),
            'code' => 'CODE'.uniqid(),
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'is_active' => 1,
            'is_current' => 0,
            'metadata' => ['foo' => 'bar'],
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $session->start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $session->end_date);
        $this->assertTrue($session->is_active);
        $this->assertFalse($session->is_current);
        $this->assertIsArray($session->metadata);
        $this->assertSame('bar', $session->metadata['foo']);
    }

    #[Test]
    public function it_can_only_have_one_current_session(): void
    {
        $first = AcademicSession::create([
            'name' => 'First '.uniqid(),
            'code' => 'CODE'.uniqid(),
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'is_current' => true,
        ]);

        AcademicSession::create([
            'name' => 'Second '.uniqid(),
            'code' => 'CODE'.uniqid(),
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_current' => true,
        ]);

        $this->assertFalse($first->fresh()->is_current);
        $this->assertEquals(1, AcademicSession::where('is_current', true)->count());
    }

    #[Test]
    public function it_returns_the_current_session(): void
    {
        AcademicSession::create([
            'name' => 'Other '.uniqid(),
            'code' => 'CODE'.uniqid(),
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'is_current' => false,
        ]);

        $current = AcademicSession::create([
            'name' => 'Current '.uniqid(),
            'code' => 'CODE'.uniqid(),
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_current' => true,
        ]);

        $this->assertNotNull(AcademicSession::current());
        $this->assertSame($current->id, AcademicSession::current()->id);
    }

    #[Test]
    public function it_scopes_active_sessions(): void
    {
        AcademicSession::create([
            'name' => 'Active '.uniqid(),
            'code' => 'CODE'.uniqid(),
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'is_active' => true,
        ]);
        AcademicSession::create([
            'name' => 'Inactive '.uniqid(),
            'code' => 'CODE'.uniqid(),
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'is_active' => false,
        ]);

        $this->assertEquals(1, AcademicSession::active()->count());
    }

    #[Test]
    public function it_soft_deletes(): void
    {
        $session = AcademicSession::create([
            'name' => 'Soft '.uniqid(),
            'code' => 'CODE'.uniqid(),
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);

        $session->delete();

        $this->assertSoftDeleted('academic_sessions', ['id' => $session->id]);
        $this->assertNull(AcademicSession::find($session->id));
        $this->assertNotNull(AcademicSession::withTrashed()->find($session->id));
    }
}
