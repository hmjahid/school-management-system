<?php
declare(strict_types=1);

namespace App\Controllers\Account;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\ActivityLog;
use App\Services\I18n;

class SettingsController extends Controller
{
    private int $customerId;

    public function __construct()
    {
        Auth::requireAuth();
        $this->customerId = (int) Auth::id();
    }

    public function index(): void
    {
        $this->view('account.settings', [
            'customer'    => Auth::user(),
            'accountPage' => 'settings',
        ]);
    }

    public function updateProfile(): void
    {
        $db = Database::getInstance();

        $data = $this->validate([
            'name'    => 'required|max:120',
            'email'   => 'required|email|max:191',
            'company' => 'max:120',
            'country' => 'max:120',
        ]);

        $exists = $db->fetch(
            "SELECT id FROM customers WHERE email = ? AND id <> ? AND deleted_at IS NULL",
            [$data['email'], $this->customerId]
        );
        if ($exists) {
            $this->withError('That email address is already in use.');
            $this->redirect('/account/settings');
        }

        $db->update('customers', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'company'    => ($_POST['company'] ?? '') !== '' ? $_POST['company'] : null,
            'country'    => ($_POST['country'] ?? '') !== '' ? $_POST['country'] : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$this->customerId]);

        $user = $db->fetch("SELECT * FROM customers WHERE id = ?", [$this->customerId]);
        if ($user) {
            Auth::login($user);
        }

        ActivityLog::log('customer.updated_profile', 'customer', $this->customerId);

        $this->withSuccess('Your profile has been updated.');
        $this->redirect('/account/settings');
    }

    public function updatePreferences(): void
    {
        $locale = (string) ($_POST['locale'] ?? 'en');
        if (!in_array($locale, array_keys(I18n::supported()), true)) {
            $locale = 'en';
        }

        Database::getInstance()->update('customers', [
            'locale'     => $locale,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$this->customerId]);

        I18n::switch($locale);

        ActivityLog::log('customer.updated_locale', 'customer', $this->customerId, ['locale' => $locale]);

        $this->withSuccess('Your language preference has been saved.');
        $this->redirect('/account/settings');
    }

    public function updatePassword(): void
    {
        $data = $this->validate([
            'current_password'          => 'required|max:72',
            'new_password'              => 'required|min:8|max:72|confirmed',
            'new_password_confirmation' => 'required|max:72',
        ]);

        $customer = Auth::user();
        if (!password_verify($data['current_password'], $customer['password'])) {
            $this->withError('Your current password is incorrect.');
            $this->redirect('/account/settings');
        }

        Database::getInstance()->update('customers', [
            'password'   => Auth::hashPassword($data['new_password']),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$this->customerId]);

        ActivityLog::log('customer.changed_password', 'customer', $this->customerId);

        $this->withSuccess('Your password has been changed.');
        $this->redirect('/account/settings');
    }
}