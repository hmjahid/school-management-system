<?php
declare(strict_types=1);

namespace App\Controllers\Account;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Gateways\GatewayFactory;
use App\Services\LicenseManager;

class RenewalController extends Controller
{
    public function __construct()
    {
        Auth::requireAuth();
    }

    public function renew(int $id): void
    {
        $db = Database::getInstance();
        $license = $db->fetch(
            "SELECT * FROM licenses WHERE id = ? AND customer_id = ? AND deleted_at IS NULL",
            [$id, (int) Auth::id()]
        );

        if (!$license) {
            $this->withError('License not found.');
            $this->redirect('/account/licenses');
        }

        $planId = (int) ($_POST['plan_id'] ?? $license['plan_id']);
        $gateway = (string) ($_POST['gateway'] ?? GatewayFactory::defaultCode());

        $result = (new LicenseManager())->renew((int) $license['id'], $planId, $gateway);

        if (($result['status'] ?? '') === 'ok') {
            $this->withSuccess($result['message'] ?: 'License renewed.');
        } else {
            $this->withError($result['message'] ?? 'Renewal failed.');
        }

        $this->redirect('/account/licenses/' . $id);
    }
}
