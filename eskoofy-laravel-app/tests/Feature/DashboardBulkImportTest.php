<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardBulkImportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        foreach (['admin', 'student', 'teacher', 'parent'] as $name) {
            Role::findOrCreate($name);
        }
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function studentCsv(): UploadedFile
    {
        $csv = implode("\n", [
            'name,email,admission_number,admission_date,class_code',
            'Jane Doe,jane@eskoofy.test,ADM-2024-0001,2024-01-15,C1',
        ]);

        return UploadedFile::fake()->createWithContent('students.csv', $csv);
    }

    private function import(): void
    {
        SchoolClass::create(['name' => 'Class 5', 'code' => 'C1']);
        $this->actingAs($this->admin())
            ->post(route('dashboard.bulk.import.store', 'students'), ['file' => $this->studentCsv()])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');
    }

    public function test_bd_profile_import_preserves_bangladeshi_defaults(): void
    {
        config(['eskoolfy.variant' => 'bd']);

        $this->import();

        $this->assertSame('ADM-2024-0001', Student::first()->admission_number);
        $this->assertSame('Bangladeshi', Student::first()->nationality);
        $this->assertSame('Bangladesh', Student::first()->country);
    }

    public function test_int_profile_import_does_not_bake_bangladeshi_defaults(): void
    {
        config(['eskoolfy.variant' => 'int']);

        $this->import();

        $this->assertSame('ADM-2024-0001', Student::first()->admission_number);
        $this->assertNull(Student::first()->nationality);
        $this->assertNull(Student::first()->country);
    }
}
