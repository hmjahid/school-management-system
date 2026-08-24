<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardBankReconciliationController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(
            $request->user()?->can('manage_chart_of_accounts') || $request->user()?->can('manage_expenses'),
            403
        );

        $bankAccounts = $this->bankAccounts();

        $data = $this->buildData($request, $bankAccounts);
        $data['bankAccounts'] = $bankAccounts;

        return view('dashboard.bank-reconciliation.index', $data);
    }

    public function reconcile(Request $request): View
    {
        abort_unless(
            $request->user()?->can('manage_chart_of_accounts') || $request->user()?->can('manage_expenses'),
            403
        );

        $bankAccounts = $this->bankAccounts();

        $data = $this->buildData($request, $bankAccounts, withReconcile: true);
        $data['bankAccounts'] = $bankAccounts;

        return view('dashboard.bank-reconciliation.index', $data);
    }

    protected function bankAccounts()
    {
        return ChartOfAccount::query()
            ->where('type', ChartOfAccount::TYPE_BANK)
            ->orWhere('code', '1010')
            ->orderBy('code')
            ->get();
    }

    protected function buildData(Request $request, $bankAccounts, bool $withReconcile = false): array
    {
        $from = $request->filled('from') ? $request->string('from')->toString() : null;
        $to = $request->filled('to') ? $request->string('to')->toString() : null;
        $accountId = $request->filled('account_id') ? $request->integer('account_id') : null;
        $statementBalance = $request->filled('statement_balance')
            ? (float) $request->string('statement_balance')->toString()
            : null;

        $entriesQuery = LedgerEntry::query()
            ->with('account')
            ->whereHas('account', function ($q) {
                $q->where('type', ChartOfAccount::TYPE_BANK)->orWhere('code', '1010');
            });

        if ($from) {
            $entriesQuery->where('date', '>=', $from);
        }
        if ($to) {
            $entriesQuery->where('date', '<=', $to);
        }
        if ($accountId) {
            $entriesQuery->where('chart_of_account_id', $accountId);
        }

        $bookBalance = (clone $entriesQuery)
            ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
            ->first();
        $bookBalance = (float) ($bookBalance->d - $bookBalance->c);

        $entries = $entriesQuery->orderByDesc('date')->orderByDesc('id')->paginate(30)->withQueryString();

        $totalDebit = (clone $entriesQuery)->sum('debit') ?: 0;
        $totalCredit = (clone $entriesQuery)->sum('credit') ?: 0;

        $difference = null;
        if ($statementBalance !== null && $withReconcile) {
            $difference = $statementBalance - $bookBalance;
        }

        return [
            'entries' => $entries,
            'from' => $from,
            'to' => $to,
            'accountId' => $accountId,
            'statementBalance' => $statementBalance,
            'bookBalance' => $bookBalance,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'difference' => $difference,
            'reconciled' => $withReconcile && $statementBalance !== null,
        ];
    }
}
