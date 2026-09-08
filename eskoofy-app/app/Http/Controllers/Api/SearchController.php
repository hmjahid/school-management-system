<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $user = $request->user();
        $students = collect();
        if ($user->hasAnyRole(['admin', 'super-admin', 'teacher'])) {
            $students = Student::whereHas('user', fn ($q) => $q->where('name', 'like', "%{$query}%"))
                ->limit(5)->get()
                ->map(fn ($s) => ['id' => $s->id, 'type' => 'student', 'name' => $s->user?->name ?? '—']);
        }

        $teachers = collect();
        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            $teachers = Teacher::whereHas('user', fn ($q) => $q->where('name', 'like', "%{$query}%"))
                ->limit(5)->get()
                ->map(fn ($t) => ['id' => $t->id, 'type' => 'teacher', 'name' => $t->user?->name ?? '—']);
        }

        $classes = SchoolClass::where('name', 'like', "%{$query}%")
            ->limit(5)->get()
            ->map(fn ($c) => ['id' => $c->id, 'type' => 'class', 'name' => $c->name]);

        return response()->json([
            'success' => true,
            'data' => array_merge($students->toArray(), $teachers->toArray(), $classes->toArray()),
        ]);
    }

    public function searchResource(Request $request, string $resource): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $user = $request->user();

        $results = match ($resource) {
            'students' => $user->hasAnyRole(['admin', 'super-admin', 'teacher'])
                ? Student::whereHas('user', fn ($q) => $q->where('name', 'like', "%{$query}%"))
                    ->limit(10)->get()
                    ->map(fn ($s) => ['id' => $s->id, 'name' => $s->user?->name ?? '—'])
                : collect(),
            'teachers' => $user->hasAnyRole(['admin', 'super-admin'])
                ? Teacher::whereHas('user', fn ($q) => $q->where('name', 'like', "%{$query}%"))
                    ->limit(10)->get()
                    ->map(fn ($t) => ['id' => $t->id, 'name' => $t->user?->name ?? '—'])
                : collect(),
            'classes' => SchoolClass::where('name', 'like', "%{$query}%")
                ->limit(10)->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
            default => collect(),
        };

        return response()->json(['success' => true, 'data' => $results]);
    }
}
