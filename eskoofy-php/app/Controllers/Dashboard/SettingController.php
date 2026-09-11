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
        $settings = $this->db->fetch("SELECT * FROM website_settings ORDER BY id DESC LIMIT 1");

        $this->view('dashboard.settings.index', ['settings' => $settings]);
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
