<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class SettingController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.settings.general', [
            'settings' => \App\Models\WebsiteSetting::getSettings(),
        ]);
    }

    public function general(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.settings.index', [
            'settings'        => \App\Models\WebsiteSetting::getSettings(),
            'librarySettings' => \App\Models\LibrarySetting::getSettings(),
            'timezones'       => \DateTimeZone::listIdentifiers(\DateTimeZone::ALL),
            'mailPresets'     => $this->mailPresets(),
        ]);
    }

    public function localization(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.settings.index', [
            'settings'        => \App\Models\WebsiteSetting::getSettings(),
            'librarySettings' => \App\Models\LibrarySetting::getSettings(),
            'timezones'       => \DateTimeZone::listIdentifiers(\DateTimeZone::ALL),
            'mailPresets'     => $this->mailPresets(),
        ]);
    }

    public function cms(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.settings.cms', [
            'settings' => \App\Models\WebsiteSetting::getSettings(),
        ]);
    }

    public function updateGeneral(): void
    {
        Auth::requireAuth();
        $this->saveGeneral();
        Session::getInstance()->flash('success', 'Settings saved.');
        $this->redirect('/dashboard/settings/general');
    }

    public function updateCms(): void
    {
        Auth::requireAuth();
        $this->saveGeneral();
        Session::getInstance()->flash('success', 'Settings saved.');
        $this->redirect('/dashboard/settings/cms');
    }

    public function updateLocalization(): void
    {
        Auth::requireAuth();
        $this->validate([
            'timezone'       => 'max:64',
            'date_format'    => 'max:20',
            'time_format'    => 'max:20',
            'default_locale' => 'in:en,bn',
        ]);
        $this->saveGeneral();
        Session::getInstance()->flash('success', 'Localization settings saved.');
        $this->redirect('/dashboard/settings/general?tab=localization');
    }

    /**
     * Mirrors the Laravel MailSettingsService provider presets.
     */
    private function mailPresets(): array
    {
        return [
            'mailtrap'  => ['host' => 'sandbox.smtp.mailtrap.io', 'port' => 2525, 'encryption' => 'tls'],
            'gmail'     => ['host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls'],
            'mailgun'   => ['host' => 'smtp.mailgun.org', 'port' => 587, 'encryption' => 'tls'],
            'ses'       => ['host' => 'email-smtp.us-east-1.amazonaws.com', 'port' => 587, 'encryption' => 'tls'],
            'postmark'  => ['host' => 'smtp.postmarkapp.com', 'port' => 587, 'encryption' => 'tls'],
            'sendgrid'  => ['host' => 'smtp.sendgrid.net', 'port' => 587, 'encryption' => 'tls'],
        ];
    }

    /**
     * Persist the general/academic/sms/localization setting fields (text,
     * booleans and uploaded logo/favicon files) onto the single
     * website_settings row, mirroring DashboardSettingController::updateGeneral.
     */
    private function saveGeneral(): void
    {
        try {
            $this->saveGeneralSettings();
        } catch (\Throwable $e) {
            Session::getInstance()->flash('error', 'Could not save settings: ' . $e->getMessage());
        }
    }

    private function saveGeneralSettings(): void
    {
        if (!\App\Core\Schema::hasTable('website_settings')) {
            throw new \RuntimeException('website_settings table is not available.');
        }

        $fields = [
            'school_name', 'school_name_bn', 'tagline', 'tagline_bn', 'email',
            'phone', 'address', 'city', 'country', 'website', 'meta_title',
            'meta_description', 'default_locale', 'timezone', 'date_format',
            'time_format', 'established_year', 'website_url', 'footer_description',
            'academic_start_month', 'facebook_url', 'twitter_url', 'instagram_url',
            'linkedin_url', 'youtube_url', 'show_facebook', 'show_twitter',
            'show_instagram', 'show_linkedin', 'show_youtube',
            'send_absence_sms', 'absence_sms_template', 'sms_sender_id',
            'twilio_sid', 'twilio_auth_token', 'twilio_from_number',
        ];

        $updateData = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $_POST)) {
                $updateData[$field] = $_POST[$field];
            }
        }

        $fileMap = [
            'logo'             => 'logo_path',
            'favicon'          => 'favicon_path',
            'footer_logo'      => 'footer_logo_path',
            'footer_logo_dark' => 'footer_logo_dark_path',
        ];
        foreach ($fileMap as $input => $column) {
            if (!empty($_FILES[$input]['tmp_name']) && $_FILES[$input]['error'] === UPLOAD_ERR_OK) {
                $updateData[$column] = $this->storeSettingFile($input);
            }
            if (!empty($_POST['remove_' . $input])) {
                $updateData[$column] = null;
            }
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $existing = $this->db->fetch("SELECT id FROM website_settings LIMIT 1");
        if ($existing) {
            $this->db->update('website_settings', $updateData, 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('website_settings', $updateData + ['created_at' => date('Y-m-d H:i:s')]);
        }
    }

    private function storeSettingFile(string $input): string
    {
        $uploadDir = __DIR__ . '/../../public/uploads/website/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES[$input]['name']));
        move_uploaded_file($_FILES[$input]['tmp_name'], $uploadDir . $filename);

        return 'uploads/website/' . $filename;
    }

    public function updateWebsite(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'school_name'     => 'max:255',
            'school_name_bn'  => 'max:255',
            'tagline'         => 'max:500',
            'email'           => 'email',
            'phone'           => 'max:50',
            'address'         => 'max:500',
            'city'            => 'max:100',
            'country'         => 'max:100',
            'website'         => 'max:255',
            'meta_title'      => 'max:255',
            'meta_description'=> 'max:500',
            'facebook_url'    => 'max:255',
            'twitter_url'     => 'max:255',
            'instagram_url'   => 'max:255',
            'linkedin_url'    => 'max:255',
            'youtube_url'     => 'max:255',
        ]);

        $updateData = array_filter($data, fn($v) => $v !== '');
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $existing = $this->db->fetch("SELECT id FROM website_settings LIMIT 1");
        if ($existing) {
            $this->db->update('website_settings', $updateData, 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('website_settings', $updateData + ['created_at' => date('Y-m-d H:i:s')]);
        }

        Session::getInstance()->flash('success', 'Website settings saved.');
        $this->redirect('/dashboard/settings');
    }

    public function updateSchool(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'school_name'    => 'max:255',
            'school_code'    => 'max:50',
            'established_year' => 'max:4',
            'EIIN'           => 'max:50',
            'registration_number' => 'max:50',
        ]);

        $updateData = array_filter($data, fn($v) => $v !== '');
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $existing = $this->db->fetch("SELECT id FROM website_settings LIMIT 1");
        if ($existing) {
            $this->db->update('website_settings', $updateData, 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('website_settings', $updateData + ['created_at' => date('Y-m-d H:i:s')]);
        }

        Session::getInstance()->flash('success', 'School settings saved.');
        $this->redirect('/dashboard/settings');
    }

    public function updateLocale(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'default_locale' => 'max:5',
            'timezone'       => 'max:64',
            'date_format'    => 'max:20',
            'time_format'    => 'max:20',
        ]);

        $updateData = array_filter($data, fn($v) => $v !== '');
        $updateData['updated_at'] = date('Y-m-d H:i:s');

        $existing = $this->db->fetch("SELECT id FROM website_settings LIMIT 1");
        if ($existing) {
            $this->db->update('website_settings', $updateData, 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('website_settings', $updateData + ['created_at' => date('Y-m-d H:i:s')]);
        }

        Session::getInstance()->flash('success', 'Locale settings saved.');
        $this->redirect('/dashboard/settings');
    }

    public function theme(): void
    {
        Auth::requireAuth();
        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");
        $this->view('dashboard.settings.theme', ['settings' => $settings]);
    }

    public function updateTheme(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'theme_color'    => 'max:20',
            'theme_mode'     => 'in:light,dark,auto',
            'logo_path'      => 'max:255',
            'favicon_path'   => 'max:255',
        ]);
        $this->saveSettings($data);
        Session::getInstance()->flash('success', 'Theme settings saved.');
        $this->redirect('/dashboard/settings/theme');
    }

    public function payment(): void
    {
        Auth::requireAuth();
        $gateways = $this->db->fetchAll("SELECT * FROM payment_gateways ORDER BY sort_order ASC, name ASC");
        $this->view('dashboard.settings.payment', ['gateways' => $gateways]);
    }

    public function updatePayment(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'default_gateway' => 'max:50',
            'currency'        => 'max:10',
        ]);
        $this->saveSettings($data);
        Session::getInstance()->flash('success', 'Payment settings saved.');
        $this->redirect('/dashboard/settings/payment');
    }

    public function mail(): void
    {
        Auth::requireAuth();
        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");
        $this->view('dashboard.settings.mail', ['settings' => $settings]);
    }

    public function testMail(): void
    {
        Auth::requireAuth();
        $to = $_POST['email'] ?? '';
        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Session::getInstance()->flash('error', 'Please provide a valid email address.');
            $this->back();
            return;
        }
        $subject = 'Test Email from Eskoofy';
        $message = "This is a test email sent from your Eskoofy system at " . date('Y-m-d H:i:s');
        $headers = 'From: ' . (config('school.email', 'noreply@eskoofy.test')) . "\r\n";
        @mail($to, $subject, $message, $headers);
        Session::getInstance()->flash('success', 'Test email sent (or queued).');
        $this->back();
    }

    public function library(): void
    {
        Auth::requireAuth();
        $settings = $this->db->fetch("SELECT * FROM library_settings ORDER BY id DESC LIMIT 1");
        $this->view('dashboard.settings.library', ['settings' => $settings]);
    }

    public function updateLibrary(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'max_books_per_user' => 'numeric',
            'issue_duration_days'=> 'numeric',
            'fine_per_day'       => 'numeric',
        ]);

        $existing = $this->db->fetch("SELECT id FROM library_settings LIMIT 1");
        $payload = array_filter($data, fn($v) => $v !== '');
        $payload['updated_at'] = date('Y-m-d H:i:s');

        if ($existing) {
            $this->db->update('library_settings', $payload, 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('library_settings', $payload + ['created_at' => date('Y-m-d H:i:s')]);
        }

        Session::getInstance()->flash('success', 'Library settings saved.');
        $this->redirect('/dashboard/settings/library');
    }

    public function globalLabels(): void
    {
        Auth::requireAuth();
        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");
        $this->view('dashboard.settings.global_labels', ['settings' => $settings]);
    }

    public function updateGlobalLabels(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'label_student'    => 'max:50',
            'label_teacher'    => 'max:50',
            'label_class'      => 'max:50',
            'label_guardian'   => 'max:50',
        ]);
        $this->saveSettings($data);
        Session::getInstance()->flash('success', 'Global labels saved.');
        $this->redirect('/dashboard/settings/global-labels');
    }

    public function about(): void
    {
        Auth::requireAuth();
        $content = $this->db->fetch("SELECT * FROM about_contents ORDER BY id DESC LIMIT 1");
        $this->view('dashboard.settings.about', ['content' => $content]);
    }

    public function updateAbout(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'mission' => 'max:5000',
            'vision'  => 'max:5000',
            'history' => 'max:5000',
            'about'   => 'max:5000',
        ]);

        $existing = $this->db->fetch("SELECT id FROM about_contents ORDER BY id DESC LIMIT 1");
        $payload = array_filter($data, fn($v) => $v !== '');
        $payload['updated_at'] = date('Y-m-d H:i:s');

        if ($existing) {
            $this->db->update('about_contents', $payload, 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('about_contents', $payload + ['created_at' => date('Y-m-d H:i:s')]);
        }

        Session::getInstance()->flash('success', 'About content saved.');
        $this->redirect('/dashboard/settings/about');
    }

    private function saveSettings(array $data): void
    {
        $updateData = array_filter($data, fn($v) => $v !== '');
        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $existing = $this->db->fetch("SELECT id FROM website_settings LIMIT 1");
        if ($existing) {
            $this->db->update('website_settings', $updateData, 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('website_settings', $updateData + ['created_at' => date('Y-m-d H:i:s')]);
        }
    }
}
