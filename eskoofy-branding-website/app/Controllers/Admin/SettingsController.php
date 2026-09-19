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
            'site.support_email'    => trim((string) ($_POST['site_support_email'] ?? '')),
            'site.sales_email'      => trim((string) ($_POST['site_sales_email'] ?? '')),
            'site.support_phone'    => trim((string) ($_POST['site_support_phone'] ?? '')),
            'site.whatsapp'         => trim((string) ($_POST['site_whatsapp'] ?? '')),
            'site.address'          => trim((string) ($_POST['site_address'] ?? '')),
            'site.support_hours'    => trim((string) ($_POST['site_support_hours'] ?? '')),
            'site.social_facebook'  => trim((string) ($_POST['site_social_facebook'] ?? '')),
            'site.social_instagram' => trim((string) ($_POST['site_social_instagram'] ?? '')),
            'site.social_twitter'   => trim((string) ($_POST['site_social_twitter'] ?? '')),
            'site.social_linkedin'  => trim((string) ($_POST['site_social_linkedin'] ?? '')),
            'site.social_youtube'   => trim((string) ($_POST['site_social_youtube'] ?? '')),
            'site.currency_label'   => trim((string) ($_POST['site_currency_label'] ?? 'USD')),
            'support.widget_enabled' => isset($_POST['support_widget_enabled']) ? '1' : '0',
            'visitors.logging_enabled' => isset($_POST['visitors_logging_enabled']) ? '1' : '0',
            'products.dashboards.app'   => trim((string) ($_POST['products_dashboards_app'] ?? '')),
            'products.dashboards.php'   => trim((string) ($_POST['products_dashboards_php'] ?? '')),
            'products.dashboards.theme' => trim((string) ($_POST['products_dashboards_theme'] ?? '')),
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
            'services.deploy_app'   => (string) max(0, (float) ($_POST['services_deploy_app'] ?? 250)),
            'services.deploy_php_theme' => (string) max(0, (float) ($_POST['services_deploy_php_theme'] ?? 150)),
            'services.care_monthly' => (string) max(0, (float) ($_POST['services_care_monthly'] ?? 29)),
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