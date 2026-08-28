<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $results = [];

        try {
            $students = Student::whereHas('user', fn ($q) => $q->where('name', 'like', "%{$query}%"))
                ->with('user')
                ->limit(5)
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'type' => 'student',
                    'name' => $s->user?->name ?? '—',
                    'subtitle' => $s->class?->name ?? '',
                    'url' => route('dashboard.students.show', $s),
                ]);
            $results = array_merge($results, $students->toArray());
        } catch (\Throwable) {}

        try {
            $teachers = Teacher::whereHas('user', fn ($q) => $q->where('name', 'like', "%{$query}%"))
                ->with('user')
                ->limit(5)
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'type' => 'teacher',
                    'name' => $t->user?->name ?? '—',
                    'subtitle' => $t->qualification ?? '',
                    'url' => route('dashboard.teachers.show', $t),
                ]);
            $results = array_merge($results, $teachers->toArray());
        } catch (\Throwable) {}

        try {
            $classes = SchoolClass::where('name', 'like', "%{$query}%")
                ->limit(5)
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'type' => 'class',
                    'name' => $c->name,
                    'subtitle' => '',
                    'url' => route('dashboard.classes'),
                ]);
            $results = array_merge($results, $classes->toArray());
        } catch (\Throwable) {}

        try {
            $notices = Notice::where('title', 'like', "%{$query}%")
                ->orWhere('title_bn', 'like', "%{$query}%")
                ->limit(5)
                ->get()
                ->map(fn ($n) => [
                    'id' => $n->id,
                    'type' => 'notice',
                    'name' => $n->localizedTitle(),
                    'subtitle' => \Illuminate\Support\Str::limit(strip_tags($n->localizedContent()), 60),
                    'url' => route('dashboard.notices.edit', $n),
                ]);
            $results = array_merge($results, $notices->toArray());
        } catch (\Throwable) {}

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('fees')) {
                $fees = \App\Models\Fee::where('name', 'like', "%{$query}%")
                    ->limit(5)
                    ->get()
                    ->map(fn ($f) => [
                        'id' => $f->id,
                        'type' => 'fee',
                        'name' => $f->name,
                        'subtitle' => $f->schoolClass?->name ?? '',
                        'url' => route('dashboard.fees'),
                    ]);
                $results = array_merge($results, $fees->toArray());
            }
        } catch (\Throwable) {}

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('payments')) {
                $payments = \App\Models\Payment::where('invoice_no', 'like', "%{$query}%")
                    ->orWhere('reference', 'like', "%{$query}%")
                    ->limit(5)
                    ->get()
                    ->map(fn ($p) => [
                        'id' => $p->id,
                        'type' => 'payment',
                        'name' => $p->invoice_no ?? ('#'.$p->id),
                        'subtitle' => number_format((float) ($p->total ?? 0), 2).' — '.($p->payment_status ?? ''),
                        'url' => route('dashboard.fee-payments.index'),
                    ]);
                $results = array_merge($results, $payments->toArray());
            }
        } catch (\Throwable) {}

        $quickLinks = [
            ['pattern' => 'report', 'type' => 'link', 'name' => __('Reports'), 'subtitle' => '', 'url' => route('dashboard.reports')],
            ['pattern' => 'setting', 'type' => 'link', 'name' => __('Settings'), 'subtitle' => '', 'url' => route('dashboard.settings.index')],
            ['pattern' => 'user', 'type' => 'link', 'name' => __('Users'), 'subtitle' => '', 'url' => route('dashboard.users.index')],
            ['pattern' => 'role', 'type' => 'link', 'name' => __('Roles'), 'subtitle' => '', 'url' => route('dashboard.roles.index')],
            ['pattern' => 'permission', 'type' => 'link', 'name' => __('Permissions'), 'subtitle' => '', 'url' => route('dashboard.permissions.index')],
        ];
        $needle = strtolower($query);
        foreach ($quickLinks as $link) {
            if (str_contains($needle, $link['pattern']) || str_contains($link['pattern'], $needle)) {
                $results[] = $link;
            }
        }

        return response()->json(['data' => $results]);
    }
}
