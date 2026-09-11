<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class LedgerController extends Controller
{
    private DatabaseInterface $db;

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

        $where = "je.date BETWEEN ? AND ?";
        $params = [$from, $to];

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM ledger_entries je WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT je.*, je.date as entry_date, je.note as description, coa.name_en as account_name, u.name as creator_name
             FROM ledger_entries je
             LEFT JOIN chart_of_accounts coa ON je.chart_of_account_id = coa.id
             LEFT JOIN users u ON je.created_by = u.id
             WHERE {$where}
             ORDER BY je.date DESC, je.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $accounts = $this->db->fetchAll("SELECT id, name_en FROM chart_of_accounts WHERE is_active = 1 ORDER BY name_en ASC");

        $this->view('dashboard.ledger.journal', [
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'from'     => $from,
            'to'       => $to,
            'accounts' => $accounts,
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
        ]);

        $account = $this->db->fetch("SELECT id FROM chart_of_accounts WHERE name_en = ? LIMIT 1", [$data['account_name']]);
        if (!$account) {
            $this->db->insert('chart_of_accounts', [
                'code'       => 'GEN-' . substr(md5((string) $data['account_name']), 0, 8),
                'name_en'    => $data['account_name'],
                'type'       => 'expense',
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $account = $this->db->fetch("SELECT id FROM chart_of_accounts WHERE name_en = ? LIMIT 1", [$data['account_name']]);
        }

        $this->db->insert('ledger_entries', [
            'chart_of_account_id' => $account['id'],
            'date'           => $data['entry_date'],
            'debit'          => $data['debit'],
            'credit'         => $data['credit'],
            'note'           => $data['description'] ?? null,
            'created_by'     => Auth::id(),
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
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
            "SELECT je.*, je.date as entry_date, je.note as description, coa.name_en as account_name
             FROM ledger_entries je
             LEFT JOIN chart_of_accounts coa ON je.chart_of_account_id = coa.id
             WHERE LOWER(coa.name_en) LIKE '%cash%' AND je.date BETWEEN ? AND ?
             ORDER BY je.date ASC",
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
            "SELECT je.*, je.date as entry_date, je.note as description, coa.name_en as account_name
             FROM ledger_entries je
             LEFT JOIN chart_of_accounts coa ON je.chart_of_account_id = coa.id
             WHERE (LOWER(coa.name_en) LIKE '%bank%' OR LOWER(coa.name_en) LIKE '%bkash%' OR LOWER(coa.name_en) LIKE '%rocket%' OR LOWER(coa.name_en) LIKE '%nagad%')
             AND je.date BETWEEN ? AND ?
             ORDER BY je.date ASC",
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
            "SELECT COALESCE(SUM(je.debit), 0) as total
             FROM ledger_entries je LEFT JOIN chart_of_accounts coa ON je.chart_of_account_id = coa.id
             WHERE coa.type = 'income' AND je.date BETWEEN ? AND ?",
            [$from, $to]
        )['total'] ?? 0);

        $expenses = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(je.credit), 0) as total
             FROM ledger_entries je LEFT JOIN chart_of_accounts coa ON je.chart_of_account_id = coa.id
             WHERE coa.type = 'expense' AND je.date BETWEEN ? AND ?",
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
            "SELECT COALESCE(SUM(je.debit) - SUM(je.credit), 0) as total
             FROM ledger_entries je LEFT JOIN chart_of_accounts coa ON je.chart_of_account_id = coa.id
             WHERE coa.type = 'asset' AND je.date <= ?",
            [$asOf]
        )['total'] ?? 0);

        $liabilities = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(je.credit) - SUM(je.debit), 0) as total
             FROM ledger_entries je LEFT JOIN chart_of_accounts coa ON je.chart_of_account_id = coa.id
             WHERE coa.type = 'liability' AND je.date <= ?",
            [$asOf]
        )['total'] ?? 0);

        $equity = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(je.credit) - SUM(je.debit), 0) as total
             FROM ledger_entries je LEFT JOIN chart_of_accounts coa ON je.chart_of_account_id = coa.id
             WHERE coa.type = 'equity' AND je.date <= ?",
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
            "SELECT COALESCE(SUM(je.credit), 0) as total
             FROM ledger_entries je LEFT JOIN chart_of_accounts coa ON je.chart_of_account_id = coa.id
             WHERE coa.type = 'income' AND je.date BETWEEN ? AND ?",
            [$from, $to]
        )['total'] ?? 0);

        $operatingOut = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(je.debit), 0) as total
             FROM ledger_entries je LEFT JOIN chart_of_accounts coa ON je.chart_of_account_id = coa.id
             WHERE coa.type = 'expense' AND je.date BETWEEN ? AND ?",
            [$from, $to]
        )['total'] ?? 0);

        $investingIn = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(je.debit), 0) as total
             FROM ledger_entries je LEFT JOIN chart_of_accounts coa ON je.chart_of_account_id = coa.id
             WHERE coa.type = 'asset' AND je.date BETWEEN ? AND ?",
            [$from, $to]
        )['total'] ?? 0);

        $financingIn = (float) ($this->db->fetch(
            "SELECT COALESCE(SUM(je.credit), 0) as total
             FROM ledger_entries je LEFT JOIN chart_of_accounts coa ON je.chart_of_account_id = coa.id
             WHERE (coa.type = 'liability' OR coa.type = 'equity') AND je.date BETWEEN ? AND ?",
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