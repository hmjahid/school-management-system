<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardNotificationTemplateController extends Controller
{
    public function index(): View
    {
        return view('dashboard.notifications.templates', [
            'templates' => NotificationTemplate::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'key' => ['required', 'string', 'max:191', 'unique:notification_templates,key'],
            'subject' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'sms_content' => ['nullable', 'string'],
            'in_app_content' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        NotificationTemplate::create([
            'name' => $data['name'],
            'key' => strtolower((string) preg_replace('/[^a-z0-9._-]/i', '-', $data['key'])),
            'subject' => $data['subject'] ?? null,
            'content' => $data['content'] ?? null,
            'sms_content' => $data['sms_content'] ?? null,
            'in_app_content' => $data['in_app_content'] ?? null,
            'variables' => [],
            'is_active' => ! empty($data['is_active']),
        ]);

        return back()->with('status', __('Notification template created.'));
    }

    public function update(Request $request, NotificationTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'key' => ['required', 'string', 'max:191', 'unique:notification_templates,key,'.$template->id],
            'subject' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'sms_content' => ['nullable', 'string'],
            'in_app_content' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $template->update([
            'name' => $data['name'],
            'key' => strtolower((string) preg_replace('/[^a-z0-9._-]/i', '-', $data['key'])),
            'subject' => $data['subject'] ?? null,
            'content' => $data['content'] ?? null,
            'sms_content' => $data['sms_content'] ?? null,
            'in_app_content' => $data['in_app_content'] ?? null,
            'is_active' => ! empty($data['is_active']),
        ]);

        return back()->with('status', __('Notification template updated.'));
    }

    public function destroy(NotificationTemplate $template): RedirectResponse
    {
        $template->delete();

        return back()->with('status', __('Notification template deleted.'));
    }
}
