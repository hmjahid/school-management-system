<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class DashboardActivityController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('view_audit_log'), 403);

        $query = Activity::with(['causer', 'subject'])->latest();

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->string('log_name')->toString());
        }
        if ($request->filled('causer_id')) {
            $query->where('causer_id', (int) $request->integer('causer_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->string('from')->toString());
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->string('to')->toString());
        }

        $rows = $query->paginate(25)->withQueryString();
        $logNames = Activity::query()->select('log_name')->whereNotNull('log_name')->distinct()->pluck('log_name');

        return view('dashboard.activity.index', compact('rows', 'logNames'));
    }
}
