<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;

/**
 * Website settings API — public summary + admin index/update.
 * Parity with eskoofy-app Admin\WebsiteSettingController (publicSettings,
 * index, update).
 */
class WebsiteSettingController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function publicSettings(): void
    {
        $row = $this->db->fetch("SELECT * FROM website_settings LIMIT 1");
        if (! $row) {
            $this->success([], 'Website settings retrieved');
            return;
        }
        $this->success([
            'school_name' => $row['school_name'] ?? null,
            'school_name_bn' => $row['school_name_bn'] ?? null,
            'tagline'     => $row['tagline'] ?? null,
            'tagline_bn'  => $row['tagline_bn'] ?? null,
            'logo_path'   => $row['logo_path'] ?? null,
            'og_image_path' => $row['og_image_path'] ?? null,
            'favicon_path' => $row['favicon_path'] ?? null,
            'address'     => $row['address'] ?? null,
            'city'        => $row['city'] ?? null,
            'phone'       => $row['phone'] ?? null,
            'email'       => $row['email'] ?? null,
            'website'     => $row['website'] ?? null,
            'social'      => [
                'facebook'  => $row['facebook_url'] ?? null,
                'twitter'   => $row['twitter_url'] ?? null,
                'instagram' => $row['instagram_url'] ?? null,
                'linkedin'  => $row['linkedin_url'] ?? null,
            ],
        ], 'Website settings retrieved');
    }

    public function index(): void
    {
        $this->requireAdmin();
        $row = $this->db->fetch("SELECT * FROM website_settings LIMIT 1");
        $this->success($row ?: [], 'Website settings retrieved');
    }

    public function update(): void
    {
        $this->requireAdmin();
        $updates = ['updated_at' => date('Y-m-d H:i:s')];
        $fields = [
            'school_name', 'school_name_bn', 'tagline', 'tagline_bn', 'logo_path',
            'og_image_path', 'favicon_path', 'footer_logo_path', 'footer_logo_dark_path',
            'established_year', 'address', 'city', 'state', 'country', 'postal_code',
            'phone', 'email', 'website', 'facebook_url', 'twitter_url', 'instagram_url',
            'linkedin_url',
        ];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $updates[$field] = $_POST[$field];
            }
        }
        foreach (['show_facebook', 'show_twitter', 'show_instagram', 'show_linkedin'] as $flag) {
            if (isset($_POST[$flag])) {
                $updates[$flag] = (int) $_POST[$flag];
            }
        }

        $existing = $this->db->fetch("SELECT id FROM website_settings LIMIT 1");
        if ($existing) {
            $this->db->update('website_settings', $updates, 'id = ?', [(int) $existing['id']]);
        } else {
            $updates['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('website_settings', $updates);
        }
        $this->success([], 'Website settings updated');
    }

    private function requireAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->hasRole('admin')) {
            $this->error('Forbidden.', 403);
        }
    }
}