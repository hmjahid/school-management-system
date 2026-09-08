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
        $types = NotificationPreference::getAvailableTypes();
        $channels = NotificationPreference::getAvailableChannels();
        $preferences = NotificationPreference::getUserPreferences($user->id);

        return view('dashboard.notifications.preferences', [
            'preferences' => $preferences,
            'types' => $types,
            'channels' => $channels,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*' => ['required', 'array'],
            'preferences.*.*' => ['nullable', 'boolean'],
        ]);

        NotificationPreference::setUserPreferences($user->id, $data['preferences']);

        return back()->with('status', __('Preferences saved.'));
    }
}
