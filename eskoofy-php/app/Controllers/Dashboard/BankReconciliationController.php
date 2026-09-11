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
            "SELECT le.*, le.date as entry_date, coa.name_en as account_name FROM ledger_entries le
             JOIN chart_of_accounts coa ON le.chart_of_account_id = coa.id
             WHERE (coa.name_en LIKE '%Bank%' OR coa.name_en LIKE '%bKash%')
             ORDER BY le.date DESC LIMIT 100"
        );

        $statementBalance = (float) ($db->fetch(
            "SELECT COALESCE(SUM(le.credit) - SUM(le.debit), 0) as total FROM ledger_entries le
             JOIN chart_of_accounts coa ON le.chart_of_account_id = coa.id
             WHERE (coa.name_en LIKE '%Bank%' OR coa.name_en LIKE '%bKash%')"
        )['total'] ?? 0);

        $bookBalance = (float) ($db->fetch(
            "SELECT COALESCE(SUM(le.debit) - SUM(le.credit), 0) as total FROM ledger_entries le
             JOIN chart_of_accounts coa ON le.chart_of_account_id = coa.id
             WHERE (coa.name_en LIKE '%Bank%' OR coa.name_en LIKE '%bKash%')"
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
