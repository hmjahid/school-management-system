<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Settings;
use App\Services\ActivityLog;

class ServiceController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $this->view('admin.services', [
            'admin'    => Auth::user(),
            'settings' => Settings::all(),
        ]);
    }

    public function update(): void
    {
        Settings::setMany([
            'services.deploy_app'      => (string) max(0, (float) ($_POST['services_deploy_app'] ?? 250)),
            'services.deploy_php_theme' => (string) max(0, (float) ($_POST['services_deploy_php_theme'] ?? 150)),
            'services.care_monthly'    => (string) max(0, (float) ($_POST['services_care_monthly'] ?? 29)),
        ]);

        ActivityLog::log('admin.updated_services', 'admin', (int) Auth::id());

        $this->withSuccess('Deployment & maintenance prices saved.');
        $this->redirect('/admin/services');
    }
}