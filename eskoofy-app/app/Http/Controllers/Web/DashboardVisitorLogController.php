<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\VisitorLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardVisitorLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = VisitorLog::with('user')->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                    ->orWhere('ip', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->paginate(50)->withQueryString();
        $totalVisits = VisitorLog::count();
        $uniqueVisitors = VisitorLog::distinct('ip')->count();
        $todayVisits = VisitorLog::whereDate('created_at', now()->toDateString())->count();
        $authenticatedVisits = VisitorLog::whereNotNull('user_id')->count();

        return view('dashboard.visitor-logs.index', compact('logs', 'totalVisits', 'uniqueVisitors', 'todayVisits', 'authenticatedVisits'));
    }
}
