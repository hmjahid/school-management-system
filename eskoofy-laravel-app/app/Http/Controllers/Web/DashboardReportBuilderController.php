<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FeePayment;
use App\Models\Payment;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardReportBuilderController extends Controller
{
    public function index(): View
    {
        return view('dashboard.reports.builder', [
            'entities' => array_keys($this->entityConfig()),
            'config' => $this->entityConfig(),
            'classes' => Schema::hasTable('school_classes') ? SchoolClass::query()->orderBy('name')->pluck('name', 'id') : collect(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $entity = $request->input('entity');
        $columns = $request->input('columns', []);
        $config = $this->entityConfig();

        if (! isset($config[$entity]) || empty($columns)) {
            abort(422, 'Invalid report configuration.');
        }

        $available = collect($config[$entity]['columns'])->mapWithKeys(fn ($c) => [$c['key'] => $c['label']]);
        $selected = collect($columns)->filter(fn ($c) => $available->has($c))->values()->all();

        $filename = 'report-'.$entity.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($entity, $selected, $available, $request) {
            $out = fopen('php://output', 'w');

            // Header
            fputcsv($out, collect($selected)->map(fn ($c) => $available[$c])->all());

            $this->buildQuery($entity, $selected, $request)
                ->chunk(500, function ($rows) use ($out, $selected) {
                    foreach ($rows as $row) {
                        $line = [];
                        foreach ($selected as $col) {
                            $line[] = $this->formatValue($row->{$col} ?? null);
                        }
                        fputcsv($out, $line);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function buildQuery(string $entity, array $selected, Request $request): mixed
    {
        $from = $request->input('date_from');
        $to = $request->input('date_to');
        $status = $request->input('status');
        $classId = $request->input('class_id');

        return match ($entity) {
            'students' => Student::query()
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($classId, fn ($q) => $q->where('class_id', $classId))
                ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                ->select($selected),
            'payments' => Payment::query()
                ->when($status, fn ($q) => $q->where('payment_status', $status))
                ->when($from, fn ($q) => $q->whereDate('payment_date', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('payment_date', '<=', $to))
                ->select($selected),
            'fee_payments' => FeePayment::query()
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($from, fn ($q) => $q->whereDate('payment_date', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('payment_date', '<=', $to))
                ->select($selected),
            default => throw new \InvalidArgumentException('Unknown entity'),
        };
    }

    private function formatValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string) ($value ?? '');
    }

    /**
     * @return array<string, array{name: string, columns: array<int, array{key: string, label: string}>}>
     */
    private function entityConfig(): array
    {
        return [
            'students' => [
                'name' => __('Students'),
                'columns' => [
                    ['key' => 'id', 'label' => __('ID')],
                    ['key' => 'first_name', 'label' => __('First name')],
                    ['key' => 'last_name', 'label' => __('Last name')],
                    ['key' => 'roll_number', 'label' => __('Roll number')],
                    ['key' => 'gender', 'label' => __('Gender')],
                    ['key' => 'status', 'label' => __('Status')],
                    ['key' => 'class_id', 'label' => __('Class ID')],
                    ['key' => 'batch_id', 'label' => __('Batch ID')],
                    ['key' => 'phone', 'label' => __('Phone')],
                    ['key' => 'address', 'label' => __('Address')],
                    ['key' => 'created_at', 'label' => __('Registered')],
                ],
            ],
            'payments' => [
                'name' => __('Payments'),
                'columns' => [
                    ['key' => 'id', 'label' => __('ID')],
                    ['key' => 'paymentable_id', 'label' => __('Payer ID')],
                    ['key' => 'amount', 'label' => __('Amount')],
                    ['key' => 'paid_amount', 'label' => __('Paid amount')],
                    ['key' => 'payment_status', 'label' => __('Status')],
                    ['key' => 'payment_method', 'label' => __('Method')],
                    ['key' => 'payment_date', 'label' => __('Date')],
                    ['key' => 'transaction_id', 'label' => __('Transaction ID')],
                ],
            ],
            'fee_payments' => [
                'name' => __('Fee payments'),
                'columns' => [
                    ['key' => 'id', 'label' => __('ID')],
                    ['key' => 'student_id', 'label' => __('Student ID')],
                    ['key' => 'amount', 'label' => __('Amount')],
                    ['key' => 'paid_amount', 'label' => __('Paid')],
                    ['key' => 'balance', 'label' => __('Balance')],
                    ['key' => 'status', 'label' => __('Status')],
                    ['key' => 'payment_date', 'label' => __('Date')],
                    ['key' => 'payment_method', 'label' => __('Method')],
                ],
            ],
        ];
    }
}
