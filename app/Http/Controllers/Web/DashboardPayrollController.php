<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Models\SalaryStructure;
use App\Models\Teacher;
use App\Services\LedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardPayrollController extends Controller
{
    public function __construct(public LedgerService $ledger) {}

    public function structures(Request $request): View
    {
        abort_unless($request->user()?->can('manage_teacher_salaries'), 403);

        $rows = SalaryStructure::with('teacher.user')->orderByDesc('is_active')->orderByDesc('effective_from')->paginate(20);

        return view('dashboard.payroll.structures', [
            'rows' => $rows,
            'teachers' => Teacher::with('user')->orderBy('id')->limit(200)->get(),
        ]);
    }

    public function storeStructure(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_teacher_salaries'), 403);

        $data = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'basic' => ['required', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'string'],
            'deductions' => ['nullable', 'string'],
            'effective_from' => ['required', 'date'],
        ]);

        $allowances = $this->parseItems($data['allowances'] ?? '');
        $deductions = $this->parseItems($data['deductions'] ?? '');

        // Deactivate previous active structures for this teacher
        SalaryStructure::where('teacher_id', $data['teacher_id'])->where('is_active', true)->update(['is_active' => false]);

        SalaryStructure::create([
            'teacher_id' => $data['teacher_id'],
            'basic' => $data['basic'],
            'allowances' => $allowances,
            'deductions' => $deductions,
            'effective_from' => $data['effective_from'],
            'is_active' => true,
        ]);

        return redirect()->route('dashboard.payroll.structures')->with('status', __('Salary structure saved.'));
    }

    public function generate(Request $request): View
    {
        abort_unless($request->user()?->can('manage_teacher_salaries'), 403);

        $month = (int) $request->integer('month', now()->month);
        $year = (int) $request->integer('year', now()->year);

        $teachers = Teacher::with(['user', 'activeStructure'])->whereHas('activeStructure')->get();
        $preview = $teachers->map(function ($t) use ($month, $year) {
            $s = $t->activeStructure;
            if (!$s) {
                return null;
            }
            $leaveDays = LeaveRequest::where('teacher_id', $t->id)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->where(function ($q) use ($month, $year) {
                    $q->where(function ($q2) use ($month, $year) {
                        $q2->whereYear('from_date', $year)->whereMonth('from_date', $month);
                    })->orWhere(function ($q3) use ($month, $year) {
                        $q3->whereYear('to_date', $year)->whereMonth('to_date', $month);
                    });
                })
                ->get()
                ->sum(fn ($l) => $l->days());

            $dailyRate = (float) $s->basic / 30;
            $leaveDeduction = $leaveDays * $dailyRate;

            return [
                'teacher' => $t,
                'structure' => $s,
                'basic' => (float) $s->basic,
                'allowances' => $s->totalAllowances(),
                'deductions' => $s->totalDeductions() + $leaveDeduction,
                'leave_days' => $leaveDays,
                'net' => $s->gross() - ($s->totalDeductions() + $leaveDeduction),
            ];
        })->filter();

        return view('dashboard.payroll.generate', compact('preview', 'month', 'year'));
    }

    public function generateStore(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_teacher_salaries'), 403);

        $data = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2020,2099'],
            'teacher_ids' => ['required', 'array'],
            'teacher_ids.*' => ['integer', 'exists:teachers,id'],
        ]);

        $count = 0;
        foreach ($data['teacher_ids'] as $teacherId) {
            $teacher = Teacher::with('activeStructure')->find($teacherId);
            $s = $teacher?->activeStructure;
            if (!$s) {
                continue;
            }

            // Skip if already generated
            if (Payslip::where('teacher_id', $teacherId)->where('month', $data['month'])->where('year', $data['year'])->exists()) {
                continue;
            }

            $leaveDays = LeaveRequest::where('teacher_id', $teacherId)
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->where(function ($q) use ($data) {
                    $q->where(function ($q2) use ($data) {
                        $q2->whereYear('from_date', $data['year'])->whereMonth('from_date', $data['month']);
                    })->orWhere(function ($q3) use ($data) {
                        $q3->whereYear('to_date', $data['year'])->whereMonth('to_date', $data['month']);
                    });
                })
                ->get()
                ->sum(fn ($l) => $l->days());

            $dailyRate = (float) $s->basic / 30;
            $leaveDeduction = $leaveDays * $dailyRate;
            $totalDeductions = $s->totalDeductions() + $leaveDeduction;
            $net = $s->gross() - $totalDeductions;

            Payslip::create([
                'teacher_id' => $teacherId,
                'month' => $data['month'],
                'year' => $data['year'],
                'basic' => (float) $s->basic,
                'total_allowances' => $s->totalAllowances(),
                'total_deductions' => $totalDeductions,
                'net_salary' => $net,
                'details' => [
                    'allowances' => $s->allowances ?? [],
                    'deductions' => $s->deductions ?? [],
                    'leave_days' => $leaveDays,
                    'leave_deduction' => $leaveDeduction,
                ],
                'status' => Payslip::STATUS_DRAFT,
                'generated_at' => now(),
            ]);

            $count++;
        }

        return redirect()->route('dashboard.payroll.payslips', ['month' => $data['month'], 'year' => $data['year']])
            ->with('status', __('Generated :count payslips.', ['count' => $count]));
    }

    public function payslips(Request $request): View
    {
        abort_unless($request->user()?->can('view_teacher_salaries') || $request->user()?->can('manage_teacher_salaries'), 403);

        $month = (int) $request->integer('month', now()->month);
        $year = (int) $request->integer('year', now()->year);

        $rows = Payslip::with('teacher.user')
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('teacher_id')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.payroll.payslips', compact('rows', 'month', 'year'));
    }

    public function showPayslip(Request $request, Payslip $payslip): View
    {
        abort_unless($request->user()?->can('view_teacher_salaries') || $request->user()?->can('manage_teacher_salaries'), 403);
        $payslip->load('teacher.user');
        return view('dashboard.payroll.payslip-show', compact('payslip'));
    }

    public function markPaid(Request $request, Payslip $payslip): RedirectResponse
    {
        abort_unless($request->user()?->can('manage_teacher_salaries'), 403);

        $payslip->markPaid();

        // Auto-post to ledger (debit salary expense, credit cash)
        try {
            $salaryExpense = ChartOfAccount::where('code', '5000')->first();
            $cashAccount = ChartOfAccount::where('code', '1000')->first();
            if ($salaryExpense && $cashAccount) {
                $this->ledger->postJournal([
                    ['account_id' => $salaryExpense->id, 'debit' => (float) $payslip->net_salary, 'credit' => 0],
                    ['account_id' => $cashAccount->id, 'debit' => 0, 'credit' => (float) $payslip->net_salary],
                ], $payslip, 'Salary ' . $payslip->monthName() . ' ' . $payslip->year, $request->user()->id, now()->toDateString());
            }
        } catch (\Throwable) {
            // Continue even if ledger post fails
        }

        return back()->with('status', __('Payslip marked as paid and posted to ledger.'));
    }

    protected function parseItems(string $raw): array
    {
        // Format: name:amount;name:amount
        if (trim($raw) === '') {
            return [];
        }
        $items = [];
        foreach (explode(';', $raw) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || !str_contains($segment, ':')) {
                continue;
            }
            [$name, $amount] = explode(':', $segment, 2);
            $items[] = ['name' => trim($name), 'amount' => (float) trim($amount)];
        }
        return $items;
    }
}