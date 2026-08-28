<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\DatabaseNotification;
use App\Models\Message;
use App\Models\ScheduledNotification;
use App\Models\SmsCampaign;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardCommunicationsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $allowed = $user?->hasRole('admin') || $user?->can('send_bulk_sms');

        abort_unless($allowed, 403);

        $smsCampaigns = SmsCampaign::withCount('recipients')
            ->with('creator')
            ->latest()
            ->limit(12)
            ->get();

        $scheduled = ScheduledNotification::with('creator')
            ->latest()
            ->limit(12)
            ->get();

        $announcements = Announcement::latest()->limit(6)->get();

        $notifications = DatabaseNotification::latest()
            ->limit(8)
            ->get();

        $counts = [
            'sms_sent' => SmsCampaign::where('status', SmsCampaign::STATUS_SENT)->count(),
            'sms_queued' => SmsCampaign::whereIn('status', [SmsCampaign::STATUS_DRAFT, SmsCampaign::STATUS_SENDING])->count(),
            'scheduled_pending' => ScheduledNotification::where('status', 'pending')->count(),
            'announcements' => Announcement::count(),
            'messages' => Message::count(),
        ];

        return view('dashboard.communications.index', compact(
            'smsCampaigns',
            'scheduled',
            'announcements',
            'notifications',
            'counts'
        ));
    }
}
