<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\LedgerEntry;
use App\Services\LedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardLedgerController extends Controller
{
    public function __construct(public LedgerService $ledger) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('manage_chart_of_accounts') || $request->user()?->can('manage_expenses'), 403);

        $query = LedgerEntry::query()->with(['account', 'reference']);

        if ($request->filled('from')) {
            $query->where('date', '>=', $request->string('from')->toString());
        }
        if ($request->filled('to')) {
            $query->where('date', '<=', $request->string('to')->toString());
        }
        if ($request->filled('account_id')) {
            $query->where('chart_of_account_id', (int) $request->integer('account_id'));
        }

        $rows = $query->orderByDesc('date')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('dashboard.ledger.index', [
            'rows' => $rows,
            'accounts' => ChartOfAccount::orderBy('code')->get(),
        ]);
    }

    public function journalForm(Request $request): View
    {
        abort_unless($request->user()?->can('manage_chart_of_accounts'), 403);

        return view('dashboard.ledger.journal', [
            'accounts' => ChartOfAccount::where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function journalStore(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_chart_of_accounts'), 403);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $lines = array_map(function ($line) {
            return [
                'account_id' => (int) $line['account_id'],
                'debit' => (float) ($line['debit'] ?? 0),
                'credit' => (float) ($line['credit'] ?? 0),
            ];
        }, $data['lines']);

        try {
            $this->ledger->postJournal($lines, null, $data['note'] ?? null, $request->user()->id, $data['date']);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }

        return redirect()->route('dashboard.ledger.index')->with('status', __('Journal entry posted.'));
    }

    public function cashbook(Request $request): View
    {
        abort_unless($request->user()?->can('manage_chart_of_accounts') || $request->user()?->can('manage_expenses'), 403);

        $account = ChartOfAccount::where('code', '1000')->firstOrFail();

        return $this->accountReport($request, $account, 'dashboard.ledger.cashbook', 'Cashbook');
    }

    public function bankbook(Request $request): View
    {
        abort_unless($request->user()?->can('manage_chart_of_accounts') || $request->user()?->can('manage_expenses'), 403);

        $account = ChartOfAccount::where('code', '1010')->firstOrFail();

        return $this->accountReport($request, $account, 'dashboard.ledger.bankbook', 'Bankbook');
    }

    public function incomeStatement(Request $request): View
    {
        abort_unless($request->user()?->can('view_financial_reports') || $request->user()?->can('manage_expenses'), 403);

        $from = $request->filled('from') ? $request->string('from')->toString() : now()->startOfMonth()->toDateString();
        $to = $request->filled('to') ? $request->string('to')->toString() : now()->toDateString();

        $incomeAccounts = ChartOfAccount::where('type', ChartOfAccount::TYPE_INCOME)->orderBy('code')->get();
        $expenseAccounts = ChartOfAccount::where('type', ChartOfAccount::TYPE_EXPENSE)->orderBy('code')->get();

        $incomeRows = $incomeAccounts->map(fn($a) => ['account' => $a, 'amount' => $a->balance($from, $to)])->filter(fn($r) => $r['amount'] != 0);
        $expenseRows = $expenseAccounts->map(fn($a) => ['account' => $a, 'amount' => $a->balance($from, $to)])->filter(fn($r) => $r['amount'] != 0);

        $totalIncome = $incomeRows->sum('amount');
        $totalExpense = $expenseRows->sum('amount');
        $net = $totalIncome - $totalExpense;

        return view('dashboard.reports.income-statement', compact('incomeRows', 'expenseRows', 'totalIncome', 'totalExpense', 'net', 'from', 'to'));
    }

    public function balanceSheet(Request $request): View
    {
        abort_unless($request->user()?->can('view_financial_reports') || $request->user()?->can('manage_expenses'), 403);

        $asOf = $request->filled('as_of') ? $request->string('as_of')->toString() : now()->toDateString();

        $assets = ChartOfAccount::where('type', ChartOfAccount::TYPE_ASSET)->orderBy('code')->get()
            ->map(fn($a) => ['account' => $a, 'amount' => $a->balance(null, $asOf)]);
        $liabilities = ChartOfAccount::where('type', ChartOfAccount::TYPE_LIABILITY)->orderBy('code')->get()
            ->map(fn($a) => ['account' => $a, 'amount' => $a->balance(null, $asOf)]);
        $equity = ChartOfAccount::where('type', ChartOfAccount::TYPE_EQUITY)->orderBy('code')->get()
            ->map(fn($a) => ['account' => $a, 'amount' => $a->balance(null, $asOf)]);

        $totalAssets = $assets->sum('amount');
        $totalLiabilities = $liabilities->sum('amount');
        $totalEquity = $equity->sum('amount');

        return view('dashboard.reports.balance-sheet', compact('assets', 'liabilities', 'equity', 'totalAssets', 'totalLiabilities', 'totalEquity', 'asOf'));
    }

    public function cashFlow(Request $request): View
    {
        abort_unless($request->user()?->can('view_financial_reports') || $request->user()?->can('manage_expenses'), 403);

        $from = $request->filled('from') ? $request->string('from')->toString() : now()->startOfMonth()->toDateString();
        $to = $request->filled('to') ? $request->string('to')->toString() : now()->toDateString();

        $cash = ChartOfAccount::where('code', '1000')->first();
        $bank = ChartOfAccount::where('code', '1010')->first();

        return view('dashboard.reports.cash-flow', [
            'from' => $from,
            'to' => $to,
            'cash' => $cash,
            'bank' => $bank,
            'cashMovements' => $cash ? $cash->entries()->whereBetween('date', [$from, $to])->orderBy('date')->get() : collect(),
            'bankMovements' => $bank ? $bank->entries()->whereBetween('date', [$from, $to])->orderBy('date')->get() : collect(),
            'cashBalance' => $cash?->balance($from, $to) ?? 0,
            'bankBalance' => $bank?->balance($from, $to) ?? 0,
        ]);
    }

    protected function accountReport(Request $request, ChartOfAccount $account, string $view, string $title): View
    {
        $from = $request->filled('from') ? $request->string('from')->toString() : now()->startOfMonth()->toDateString();
        $to = $request->filled('to') ? $request->string('to')->toString() : now()->toDateString();

        $entries = $account->entries()->whereBetween('date', [$from, $to])->orderBy('date')->orderBy('id')->get();
        $opening = $account->balance(null, $from);
        $closing = $account->balance(null, $to);

        return view($view, [
            'account' => $account,
            'title' => $title,
            'from' => $from,
            'to' => $to,
            'entries' => $entries,
            'opening' => $opening,
            'closing' => $closing,
        ]);
    }
}