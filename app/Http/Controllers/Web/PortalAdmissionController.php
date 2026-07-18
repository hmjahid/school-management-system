<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalAdmissionController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        $admission = Admission::query()
            ->with(['documents', 'tests' => fn ($q) => $q->orderByDesc('scheduled_at'), 'academicSession', 'batch'])
            ->where('email', $user->email)
            ->orderByDesc('id')
            ->first();

        return view('site.portal-admission', compact('admission'));
    }
}
