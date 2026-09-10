<?php
declare(strict_types=1);

namespace App\Controllers\Account;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class PaymentController extends Controller
{
    public function __construct()
    {
        Auth::requireAuth();
    }

    public function index(): void
    {
        $rows = Database::getInstance()->fetchAll(
            "SELECT p.*, pl.name AS plan_name, li.license_key
             FROM payments p
             LEFT JOIN plans pl ON p.plan_id = pl.id
             LEFT JOIN licenses li ON p.license_id = li.id
             WHERE p.customer_id = ?
             ORDER BY p.id DESC",
            [(int) Auth::id()]
        );

        $this->view('account.payments', [
            'customer' => Auth::user(),
            'payments' => $rows,
        ]);
    }
}
