<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class LedgerController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $this->journal();
    }

    public function journal(): void
    {
        Auth::requireAuth();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = "je.entry_date BETWEEN ? AND ?";
        $params = [$from, $to];

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM journal_entries je WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT je.*, u.name as creator_name
             FROM journal_entries je
             LEFT JOIN users u ON je.created_by = u.id
             WHERE {$where}
             ORDER BY je.entry_date DESC, je.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $this->view('dashboard.ledger.journal', [
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'from'     => $from,
            'to'       => $to,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'account_name' => 'required|max:255',
            'debit'        => 'required|numeric',
            'credit'       => 'required|numeric',
            'entry_date'   => 'required',
            'description'  => 'max:500',
            'reference'    => 'max:100',
        ]);

        $this->db->insert('journal_entries', [
            'account_name' => $data['account_name'],
            'debit'        => $data['debit'],
            'credit'       => $data['credit'],
            'entry_date'   => $data['entry_date'],
            'description'  => $data['description'] ?? null,
            'reference'    => $data['reference'] ?? null,
            'created_by'   => Auth::id(),
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Journal entry created.');
        $this->redirect('/dashboard/ledger/journal');
    }

    public function cashbook(): void
    {
        Auth::requireAuth();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');

        $rows = $this->db->fetchAll(
            "SELECT je.*
             FROM journal_entries je
             WHERE je.account_name LIKE '%Cash%' AND je.entry_date BETWEEN ? AND ?
             ORDER BY je.entry_date ASC",
            [$from, $to]
        );

        $this->view('dashboard.ledger.cashbook', [
            'rows' => $rows,
            'from' => $from,
            'to'   => $to,
        ]);
    }

    public function bankbook(): void
    {
        Auth::requireAuth();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');

        $rows = $this->db->fetchAll(
            "SELECT je.*
             FROM journal_entries je
             WHERE (je.account_name LIKE '%Bank%' OR je.account_name LIKE '%bKash%' OR je.account_name LIKE '%Rocket%')
             AND je.entry_date BETWEEN ? AND ?
             ORDER BY je.entry_date ASC",
            [$from, $to]
        );

        $this->view('dashboard.ledger.bankbook', [
            'rows' => $rows,
            'from' => $from,
            'to'   => $to,
        ]);
    }

    public function incomeStatement(): void
    {
        Auth::requireAuth();
        $from = $_GET['from'] ?? date('Y-01-01');
        $to = $_GET['to'] ?? date('Y-m-d');

        $income = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(debit), 0) as total FROM journal_entries WHERE account_name LIKE '%Income%' AND entry_date BETWEEN ? AND ?",
            [$from, $to]
        )['total'] ?? 0);

        $expenses = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(credit), 0) as total FROM journal_entries WHERE account_name LIKE '%Expense%' AND entry_date BETWEEN ? AND ?",
            [$from, $to]
        )['total'] ?? 0);

        $this->view('dashboard.ledger.income_statement', [
            'income'   => $income,
            'expenses' => $expenses,
            'net'      => $income - $expenses,
            'from'     => $from,
            'to'       => $to,
        ]);
    }

    public function balanceSheet(): void
    {
        Auth::requireAuth();
        $asOf = $_GET['as_of'] ?? date('Y-m-d');

        $assets = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(debit) - SUM(credit), 0) as total FROM journal_entries WHERE account_name LIKE '%Asset%' AND entry_date <= ?",
            [$asOf]
        )['total'] ?? 0);

        $liabilities = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(credit) - SUM(debit), 0) as total FROM journal_entries WHERE account_name LIKE '%Liability%' AND entry_date <= ?",
            [$asOf]
        )['total'] ?? 0);

        $equity = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(credit) - SUM(debit), 0) as total FROM journal_entries WHERE account_name LIKE '%Equity%' AND entry_date <= ?",
            [$asOf]
        )['total'] ?? 0);

        $this->view('dashboard.ledger.balance_sheet', [
            'assets'       => $assets,
            'liabilities'  => $liabilities,
            'equity'       => $equity,
            'asOf'         => $asOf,
        ]);
    }

    public function cashFlow(): void
    {
        Auth::requireAuth();
        $from = $_GET['from'] ?? date('Y-01-01');
        $to = $_GET['to'] ?? date('Y-m-d');

        $operatingIn = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(credit), 0) as total FROM journal_entries
             WHERE account_name LIKE '%Income%' AND entry_date BETWEEN ? AND ?",
            [$from, $to]
        )['total'] ?? 0);

        $operatingOut = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(debit), 0) as total FROM journal_entries
             WHERE account_name LIKE '%Expense%' AND entry_date BETWEEN ? AND ?",
            [$from, $to]
        )['total'] ?? 0);

        $investingIn = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(debit), 0) as total FROM journal_entries
             WHERE account_name LIKE '%Asset%' AND entry_date BETWEEN ? AND ?",
            [$from, $to]
        )['total'] ?? 0);

        $financingIn = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(credit), 0) as total FROM journal_entries
             WHERE account_name LIKE '%Liability%' OR account_name LIKE '%Equity%'
             AND entry_date BETWEEN ? AND ?",
            [$from, $to]
        )['total'] ?? 0);

        $netOperating = $operatingIn - $operatingOut;
        $netInvesting = -$investingIn;
        $netFinancing = $financingIn;
        $netCashFlow = $netOperating + $netInvesting + $netFinancing;

        $this->view('dashboard.ledger.cash_flow', [
            'from'          => $from,
            'to'            => $to,
            'operatingIn'   => $operatingIn,
            'operatingOut'  => $operatingOut,
            'netOperating'  => $netOperating,
            'investingIn'   => $investingIn,
            'netInvesting'  => $netInvesting,
            'financingIn'   => $financingIn,
            'netFinancing'  => $netFinancing,
            'netCashFlow'   => $netCashFlow,
        ]);
    }
}
