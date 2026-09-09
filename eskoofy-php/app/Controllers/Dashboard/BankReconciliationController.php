<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class BankReconciliationController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $bankEntries = $db->fetchAll(
            "SELECT * FROM journal_entries
             WHERE (account_name LIKE '%Bank%' OR account_name LIKE '%bKash%')
             ORDER BY entry_date DESC LIMIT 100"
        );

        $statementBalance = (float) ($db->fetch(
            "SELECT COALESCE(SUM(credit) - SUM(debit), 0) as total FROM journal_entries
             WHERE (account_name LIKE '%Bank%' OR account_name LIKE '%bKash%')"
        )['total'] ?? 0);

        $bookBalance = (float) ($db->fetch(
            "SELECT COALESCE(SUM(debit) - SUM(credit), 0) as total FROM journal_entries
             WHERE (account_name LIKE '%Bank%' OR account_name LIKE '%bKash%')"
        )['total'] ?? 0);

        $this->view('dashboard.bank_reconciliation.index', [
            'bankEntries'     => $bankEntries,
            'statementBalance'=> $statementBalance,
            'bookBalance'     => $bookBalance,
            'difference'      => $statementBalance - $bookBalance,
        ]);
    }

    public function reconcile(): void
    {
        Auth::requireAuth();
        Session::getInstance()->flash('success', 'Reconciliation saved.');
        $this->redirect('/dashboard/bank-reconciliation');
    }
}
