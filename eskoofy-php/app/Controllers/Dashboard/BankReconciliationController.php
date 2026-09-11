<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Support\Collection;

class BankReconciliationController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $db = Database::getInstance();

        $from = $_GET['from'] ?? '';
        $to = $_GET['to'] ?? '';
        $accountId = (int) ($_GET['account_id'] ?? 0);
        $statementBalance = ($_GET['statement_balance'] ?? '') !== '' ? (float) $_GET['statement_balance'] : null;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 30;

        $bankEntries = $db->fetchAll(
            "SELECT le.*, le.date as entry_date, coa.name_en as account_name FROM ledger_entries le
             JOIN chart_of_accounts coa ON le.chart_of_account_id = coa.id
             WHERE (coa.name_en LIKE '%Bank%' OR coa.name_en LIKE '%bKash%')
             ORDER BY le.date DESC"
        );
        $entries = $this->paginateRows($bankEntries, count($bankEntries), $perPage, $page, \App\Models\LedgerEntry::class);

        $bankAccounts = \App\Models\ChartOfAccount::hydrate($db->fetchAll(
            "SELECT id, code, name_en, name_bn FROM chart_of_accounts WHERE is_active = 1 ORDER BY code"
        ));

        $bookBalance = (float) ($db->fetch(
            "SELECT COALESCE(SUM(le.debit) - SUM(le.credit), 0) as total FROM ledger_entries le
             JOIN chart_of_accounts coa ON le.chart_of_account_id = coa.id
             WHERE (coa.name_en LIKE '%Bank%' OR coa.name_en LIKE '%bKash%')"
        )['total'] ?? 0);

        $totalDebit = (float) ($db->fetch(
            "SELECT COALESCE(SUM(le.debit), 0) as total FROM ledger_entries le
             JOIN chart_of_accounts coa ON le.chart_of_account_id = coa.id
             WHERE (coa.name_en LIKE '%Bank%' OR coa.name_en LIKE '%bKash%')"
        )['total'] ?? 0);

        $totalCredit = (float) ($db->fetch(
            "SELECT COALESCE(SUM(le.credit), 0) as total FROM ledger_entries le
             JOIN chart_of_accounts coa ON le.chart_of_account_id = coa.id
             WHERE (coa.name_en LIKE '%Bank%' OR coa.name_en LIKE '%bKash%')"
        )['total'] ?? 0);

        $difference = $statementBalance !== null ? $statementBalance - $bookBalance : null;

        $this->view('dashboard.bank_reconciliation.index', [
            'entries'          => $entries,
            'bankAccounts'     => new Collection($bankAccounts),
            'accountId'        => $accountId,
            'from'             => $from,
            'to'               => $to,
            'statementBalance' => $statementBalance,
            'bookBalance'      => $bookBalance,
            'totalDebit'       => $totalDebit,
            'totalCredit'      => $totalCredit,
            'difference'       => $difference,
            'reconciled'       => false,
        ]);
    }

    public function reconcile(): void
    {
        Auth::requireAuth();
        Session::getInstance()->flash('success', 'Reconciliation saved.');
        $this->redirect('/dashboard/bank-reconciliation');
    }
}
