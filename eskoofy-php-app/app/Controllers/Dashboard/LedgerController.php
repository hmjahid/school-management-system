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
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');

        $incomeRows = $this->reportRows('income', $from, $to);
        $expenseRows = $this->reportRows('expense', $from, $to);
        $totalIncome = array_sum(array_column($incomeRows, 'amount'));
        $totalExpense = array_sum(array_column($expenseRows, 'amount'));
        $net = $totalIncome - $totalExpense;

        $this->view('dashboard.reports.income-statement', [
            'incomeRows'   => $incomeRows,
            'expenseRows'  => $expenseRows,
            'totalIncome'  => $totalIncome,
            'totalExpense' => $totalExpense,
            'net'          => $net,
            'from'         => $from,
            'to'           => $to,
        ]);
    }

    public function balanceSheet(): void
    {
        Auth::requireAuth();
        $asOf = $_GET['as_of'] ?? date('Y-m-d');

        $assets = $this->reportRows('asset', null, $asOf);
        $liabilities = $this->reportRows('liability', null, $asOf);
        $equity = $this->reportRows('equity', null, $asOf);
        $totalAssets = array_sum(array_column($assets, 'amount'));
        $totalLiabilities = array_sum(array_column($liabilities, 'amount'));
        $totalEquity = array_sum(array_column($equity, 'amount'));

        $this->view('dashboard.reports.balance-sheet', [
            'assets'           => $assets,
            'liabilities'      => $liabilities,
            'equity'           => $equity,
            'totalAssets'      => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity'      => $totalEquity,
            'asOf'             => $asOf,
        ]);
    }

    public function cashFlow(): void
    {
        Auth::requireAuth();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');

        $cash = $this->db->fetch("SELECT id FROM chart_of_accounts WHERE code = '1000' LIMIT 1");
        $bank = $this->db->fetch("SELECT id FROM chart_of_accounts WHERE code = '1010' LIMIT 1");

        $cashMovements = $this->movements($cash['id'] ?? null, $from, $to);
        $bankMovements = $this->movements($bank['id'] ?? null, $from, $to);

        $this->view('dashboard.reports.cash-flow', [
            'from'          => $from,
            'to'            => $to,
            'cashMovements' => $cashMovements,
            'bankMovements' => $bankMovements,
            'cashBalance'   => $this->movementBalance($cashMovements),
            'bankBalance'   => $this->movementBalance($bankMovements),
        ]);
    }

    /**
     * Per-account period balances as [['account' => ChartOfAccount, 'amount' => float]].
     * Asset/expense accounts report debit - credit; liability/income/equity
     * report credit - debit (mirrors the app's ChartOfAccount::balance()).
     */
    private function reportRows(string $type, ?string $from, ?string $to): array
    {
        $dateSql = '';
        $params = [];
        if ($from !== null) {
            $dateSql .= ' AND je.date >= ?';
            $params[] = $from;
        }
        if ($to !== null) {
            $dateSql .= ' AND je.date <= ?';
            $params[] = $to;
        }

        $sql = "SELECT coa.id, coa.name_en, COALESCE(SUM(je.debit), 0) as debit, COALESCE(SUM(je.credit), 0) as credit
                FROM chart_of_accounts coa
                LEFT JOIN ledger_entries je ON je.chart_of_account_id = coa.id" . $dateSql .
               " WHERE coa.type = ? AND coa.is_active = 1 GROUP BY coa.id, coa.name_en ORDER BY coa.code ASC";
        $params[] = $type;

        $rows = $this->db->fetchAll($sql, $params);
        $result = [];
        $assetLike = in_array($type, ['asset', 'expense'], true);
        foreach ($rows as $row) {
            $amount = $assetLike
                ? (float) ($row['debit'] ?? 0) - (float) ($row['credit'] ?? 0)
                : (float) ($row['credit'] ?? 0) - (float) ($row['debit'] ?? 0);
            if ($amount == 0) {
                continue;
            }
            $result[] = [
                'account' => \App\Models\ChartOfAccount::newFromRow([
                    'id'      => (int) $row['id'],
                    'name_en' => $row['name_en'],
                ]),
                'amount'  => round($amount, 2),
            ];
        }
        return $result;
    }

    private function movements(?int $accountId, string $from, string $to): array
    {
        if (!$accountId) {
            return [];
        }
        $rows = $this->db->fetchAll(
            "SELECT je.*, coa.name_en as account_name
             FROM ledger_entries je
             LEFT JOIN chart_of_accounts coa ON je.chart_of_account_id = coa.id
             WHERE je.chart_of_account_id = ? AND je.date BETWEEN ? AND ?
             ORDER BY je.date ASC, je.id ASC",
            [$accountId, $from, $to]
        );
        return \App\Models\LedgerEntry::hydrate($rows);
    }

    private function movementBalance(array $movements): float
    {
        $balance = 0.0;
        foreach ($movements as $m) {
            $balance += (float) ($m->debit ?? 0) - (float) ($m->credit ?? 0);
        }
        return round($balance, 2);
    }
}