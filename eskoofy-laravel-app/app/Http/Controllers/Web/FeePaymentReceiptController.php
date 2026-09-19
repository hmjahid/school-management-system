<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FeePayment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeePaymentReceiptController extends Controller
{
    public function show(Request $request, FeePayment $feePayment): View
    {
        $user = $request->user();

        // Admin/staff can view any receipt.
        if ($user->hasAnyRole(['admin', 'accountant', 'staff'])) {
            $feePayment->load(['student.user', 'fee', 'creator', 'approver']);

            return view('site.fee-receipt', ['p' => $feePayment]);
        }

        // Student/parent can only view receipts for linked students.
        $allowedStudentIds = app(PaymentsWebController::class)->studentIdsForUser($user);
        abort_unless($allowedStudentIds->contains((int) $feePayment->student_id), 403);

        $feePayment->load(['student.user', 'fee', 'creator', 'approver']);

        return view('site.fee-receipt', ['p' => $feePayment]);
    }
}
