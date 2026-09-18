<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Settings;
use App\Services\ActivityLog;

class SettingsController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $this->view('admin.settings', [
            'admin'    => Auth::user(),
            'settings' => Settings::all(),
        ]);
    }

    public function update(): void
    {
        Settings::setMany([
            'site.name'             => trim((string) ($_POST['site_name'] ?? '')),
            'site.tagline'          => trim((string) ($_POST['site_tagline'] ?? '')),
            'site.contact_email'    => trim((string) ($_POST['site_contact_email'] ?? '')),
            'site.currency_label'   => trim((string) ($_POST['site_currency_label'] ?? 'USD')),
            'appearance.brand_color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($_POST['appearance_brand_color'] ?? ''))
                ? (string) $_POST['appearance_brand_color']
                : '#2563eb',
            'appearance.dark_default' => isset($_POST['appearance_dark_default']) ? '1' : '0',
            'email.driver'          => (string) ($_POST['email_driver'] ?? 'sendmail'),
            'email.host'            => trim((string) ($_POST['email_host'] ?? '')),
            'email.port'            => trim((string) ($_POST['email_port'] ?? '587')),
            'email.username'        => trim((string) ($_POST['email_username'] ?? '')),
            'email.encryption'      => (string) ($_POST['email_encryption'] ?? 'tls'),
            'email.from_address'    => trim((string) ($_POST['email_from_address'] ?? '')),
            'email.from_name'       => trim((string) ($_POST['email_from_name'] ?? 'Eskoofy')),
        ]);

        // Only overwrite the SMTP password when a new one is actually provided.
        if (trim((string) ($_POST['email_password'] ?? '')) !== '') {
            Settings::set('email.password', trim((string) $_POST['email_password']));
        }

        ActivityLog::log('admin.updated_settings', 'admin', (int) Auth::id());

        $this->withSuccess('Settings saved.');
        $this->redirect('/admin/settings');
    }
}