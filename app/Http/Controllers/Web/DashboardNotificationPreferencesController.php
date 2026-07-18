<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardNotificationPreferencesController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $events = ['attendance_absent', 'fee_due', 'result_published', 'announcement', 'leave_request'];
        $channels = ['in_app', 'email', 'sms'];

        $prefs = [];
        foreach ($events as $event) {
            foreach ($channels as $channel) {
                $prefs[$event][$channel] = NotificationPreference::where('user_id', $user->id)
                    ->where('event', $event)
                    ->where('channel', $channel)
                    ->value('enabled') ?? true;
            }
        }

        return view('dashboard.notifications.preferences', compact('prefs', 'events', 'channels'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.*' => ['nullable', 'boolean'],
        ]);

        foreach ($data['preferences'] as $event => $channels) {
            foreach ($channels as $channel => $enabled) {
                NotificationPreference::updateOrCreate(
                    ['user_id' => $user->id, 'event' => $event, 'channel' => $channel],
                    ['enabled' => (bool) $enabled],
                );
            }
        }

        return back()->with('status', __('Preferences saved.'));
    }
}