<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class SettingController extends Controller
{
    private Database $db;

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
}
