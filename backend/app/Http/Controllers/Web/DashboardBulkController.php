<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardBulkController extends Controller
{
    protected array $resources = [
        'students' => 'Students',
        'teachers' => 'Teachers',
    ];

    public function index(): View
    {
        return view('dashboard.bulk.index', ['resources' => $this->resources]);
    }

    public function export(Request $request, string $resource): StreamedResponse
    {
        $this->ensureResource($resource);

        if ($resource === 'students') {
            return $this->exportStudents();
        }

        return $this->exportTeachers();
    }

    public function import(Request $request, string $resource): View
    {
        $this->ensureResource($resource);

        return view('dashboard.bulk.import', [
            'resource' => $resource,
            'label' => $this->resources[$resource],
            'headers' => $resource === 'students' ? $this->studentHeaders() : $this->teacherHeaders(),
            'sample' => $resource === 'students' ? $this->studentSample() : $this->teacherSample(),
        ]);
    }

    public function importStore(Request $request, string $resource): RedirectResponse
    {
        $this->ensureResource($resource);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
            'dry_run' => 'sometimes|boolean',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        if (! $handle) {
            return back()->withErrors(['file' => __('Could not read the uploaded file.')]);
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);
            return back()->withErrors(['file' => __('Empty file.')]);
        }
        $headers = array_map(fn ($h) => Str::slug(trim($h), '_'), $headers);

        $required = $resource === 'students'
            ? ['name', 'email', 'admission_number', 'admission_date', 'class_code']
            : ['name', 'email', 'employee_id', 'joining_date'];

        $missing = array_diff($required, $headers);
        if (! empty($missing)) {
            fclose($handle);
            return back()->withErrors(['file' => __('Missing columns: :cols', ['cols' => implode(', ', $missing)])]);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            $row = 0;
            while (($data = fgetcsv($handle)) !== false) {
                $row++;
                if (count($data) === 1 && trim((string) $data[0]) === '') {
                    continue;
                }
                $row_data = array_combine($headers, array_pad($data, count($headers), null));

                try {
                    if ($resource === 'students') {
                        $result = $this->importStudent($row_data);
                    } else {
                        $result = $this->importTeacher($row_data);
                    }
                    if ($result === 'created') $created++;
                    elseif ($result === 'updated') $updated++;
                    else $skipped++;
                } catch (\Throwable $e) {
                    $errors[] = __('Row :n: :msg', ['n' => $row, 'msg' => $e->getMessage()]);
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            return back()->withErrors(['file' => $e->getMessage()]);
        }
        fclose($handle);

        $message = __('Imported: :c created, :u updated, :s skipped.', [
            'c' => $created, 'u' => $updated, 's' => $skipped,
        ]);
        if (! empty($errors)) {
            $message .= ' ' . __('Errors: :n', ['n' => count($errors)]);
        }

        return redirect()
            ->route('dashboard.bulk.import', $resource)
            ->with('status', $message)
            ->with('import_errors', $errors);
    }

    protected function importStudent(array $row): string
    {
        $email = trim((string) ($row['email'] ?? ''));
        $admission = trim((string) ($row['admission_number'] ?? ''));
        $name = trim((string) ($row['name'] ?? ''));
        if ($email === '' || $admission === '' || $name === '') {
            return 'skipped';
        }

        $classCode = trim((string) ($row['class_code'] ?? ''));
        $classId = null;
        if ($classCode !== '') {
            $class = SchoolClass::where('code', $classCode)->first();
            if (! $class) {
                throw new \RuntimeException("class_code '{$classCode}' not found");
            }
            $classId = $class->id;
        }

        $studentRole = Role::where('name', 'student')->first();
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make((string) ($row['password'] ?? 'password')),
                'email_verified_at' => now(),
                'role_id' => $studentRole?->id,
            ]
        );
        $created = ! $user->wasRecentlyCreated === false;

        if (! $user->hasRole('student')) {
            $user->assignRole('student');
        }

        $existing = Student::where('admission_number', $admission)->first();
        $nameParts = explode(' ', $name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? $nameParts[0];

        Student::updateOrCreate(
            ['admission_number' => $admission],
            [
                'user_id' => $user->id,
                'class_id' => $classId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'admission_date' => $row['admission_date'] ?? now()->toDateString(),
                'roll_number' => $row['roll_number'] ?? null,
                'gender' => $row['gender'] ?? null,
                'date_of_birth' => $row['date_of_birth'] ?? null,
                'phone' => $row['phone'] ?? null,
                'present_address' => $row['present_address'] ?? null,
                'status' => $row['status'] ?? 'active',
                'nationality' => 'Bangladeshi',
                'country' => 'Bangladesh',
            ]
        );

        return $existing ? 'updated' : ($created ? 'created' : 'updated');
    }

    protected function importTeacher(array $row): string
    {
        $email = trim((string) ($row['email'] ?? ''));
        $employee = trim((string) ($row['employee_id'] ?? ''));
        $name = trim((string) ($row['name'] ?? ''));
        if ($email === '' || $employee === '' || $name === '') {
            return 'skipped';
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make((string) ($row['password'] ?? 'password')),
                'email_verified_at' => now(),
                'role_id' => Role::where('name', 'teacher')->value('id'),
            ]
        );

        if (! $user->hasRole('teacher')) {
            $user->assignRole('teacher');
        }

        $existing = Teacher::where('employee_id', $employee)->first();
        Teacher::updateOrCreate(
            ['employee_id' => $employee],
            [
                'user_id' => $user->id,
                'phone' => $row['phone'] ?? null,
                'gender' => $row['gender'] ?? null,
                'date_of_birth' => $row['date_of_birth'] ?? null,
                'joining_date' => $row['joining_date'] ?? now()->toDateString(),
                'qualification' => $row['qualification'] ?? null,
                'specialization' => $row['specialization'] ?? null,
                'status' => $row['status'] ?? 'active',
            ]
        );

        return $existing ? 'updated' : 'created';
    }

    protected function exportStudents(): StreamedResponse
    {
        $filename = 'students-' . now()->format('Ymd-His') . '.csv';
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, $this->studentHeaders());
            Student::with(['user', 'class'])->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $s) {
                    fputcsv($out, [
                        $s->user?->name,
                        $s->user?->email,
                        $s->admission_number,
                        $s->admission_date?->format('Y-m-d'),
                        $s->class?->code,
                        $s->roll_number,
                        $s->gender,
                        $s->date_of_birth?->format('Y-m-d'),
                        $s->phone,
                        $s->present_address,
                        $s->status,
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function exportTeachers(): StreamedResponse
    {
        $filename = 'teachers-' . now()->format('Ymd-His') . '.csv';
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, $this->teacherHeaders());
            Teacher::with('user')->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $t) {
                    fputcsv($out, [
                        $t->user?->name,
                        $t->user?->email,
                        $t->employee_id,
                        $t->joining_date?->format('Y-m-d'),
                        $t->phone,
                        $t->gender,
                        $t->date_of_birth?->format('Y-m-d'),
                        $t->qualification,
                        $t->specialization,
                        $t->status,
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function studentHeaders(): array
    {
        return ['name', 'email', 'admission_number', 'admission_date', 'class_code', 'roll_number', 'gender', 'date_of_birth', 'phone', 'present_address', 'status'];
    }

    protected function teacherHeaders(): array
    {
        return ['name', 'email', 'employee_id', 'joining_date', 'phone', 'gender', 'date_of_birth', 'qualification', 'specialization', 'status'];
    }

    protected function studentSample(): array
    {
        return [
            ['Jane Doe', 'jane@example.com', 'ADM-2024-0001', '2024-01-15', 'C1', '12', 'female', '2012-05-20', '+8801711111111', 'House 1, Dhaka', 'active'],
        ];
    }

    protected function teacherSample(): array
    {
        return [
            ['John Smith', 'john@example.com', 'EMP-2024-0001', '2024-01-10', '+8801711111112', 'male', '1990-07-20', 'MSc Mathematics', 'Math', 'active'],
        ];
    }

    protected function ensureResource(string $resource): void
    {
        abort_unless(array_key_exists($resource, $this->resources), 404);
    }
}
