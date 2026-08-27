<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class QuickActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin|super-admin');
    }

    public function handle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:add_student,add_teacher,send_announcement,generate_report',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid action',
                'errors' => $validator->errors(),
            ], 422);
        }

        $action = $request->input('action');

        $result = match ($action) {
            'add_student' => $this->addStudent($request),
            'add_teacher' => $this->addTeacher($request),
            'send_announcement' => $this->sendAnnouncement($request),
            'generate_report' => $this->generateReport($request),
            default => throw new \InvalidArgumentException('Unknown action'),
        };

        return response()->json([
            'success' => true,
            'message' => 'Action completed successfully',
            'data' => $result,
        ]);
    }

    protected function addStudent(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'class_id' => 'required|exists:school_classes,id',
        ]);

        $user = User::createWithCredential([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('student');

        $student = Student::create([
            'user_id' => $user->id,
            'class_id' => $data['class_id'],
            'first_name' => $data['name'],
            'admission_date' => now(),
            'admission_number' => 'STA-'.strtoupper(uniqid()),
        ]);

        return ['student_id' => $student->id, 'user_id' => $user->id];
    }

    protected function addTeacher(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
        ]);

        $user = User::createWithCredential([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('teacher');

        $teacher = Teacher::create(['user_id' => $user->id]);

        return ['teacher_id' => $teacher->id, 'user_id' => $user->id];
    }

    protected function sendAnnouncement(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        activity()
            ->causedBy($request->user())
            ->event('announcement')
            ->log($data['title'].': '.$data['message']);

        return ['sent' => true, 'title' => $data['title']];
    }

    protected function generateReport(Request $request): array
    {
        $data = $request->validate([
            'type' => 'nullable|string|in:fees,attendance,students',
        ]);

        $type = $data['type'] ?? 'students';
        $count = match ($type) {
            'fees' => \App\Models\Fee::count(),
            'attendance' => \App\Models\Attendance::count(),
            default => Student::count(),
        };

        return ['type' => $type, 'total_records' => $count, 'generated_at' => now()->toIso8601String()];
    }
}
