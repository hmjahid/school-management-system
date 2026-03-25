<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Guardian;
use App\Models\PaymentGateway;
use App\Models\Student;
use App\Models\WebsiteContent;
use Illuminate\View\View;

class PaymentsWebController extends Controller
{
    public function index(): View
    {
        $content = WebsiteContent::getContent('payments');

        $feeRows = Fee::query()
            ->where('status', Fee::STATUS_ACTIVE)
            ->orderBy('name')
            ->limit(40)
            ->get();

        $gateways = PaymentGateway::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $feePayments = collect();
        if (auth()->check()) {
            $studentIds = $this->studentIdsForUser(auth()->user());
            if ($studentIds->isNotEmpty()) {
                $feePayments = FeePayment::query()
                    ->whereIn('student_id', $studentIds)
                    ->orderByDesc('payment_date')
                    ->orderByDesc('id')
                    ->limit(25)
                    ->get();
            }
        }

        return view('site.payments', compact('content', 'feeRows', 'gateways', 'feePayments'));
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    protected function studentIdsForUser(\App\Models\User $user): \Illuminate\Support\Collection
    {
        if ($user->hasRole('student')) {
            $id = Student::query()->where('user_id', $user->id)->value('id');

            return $id ? collect([$id]) : collect();
        }

        if ($user->hasRole('parent')) {
            $guardian = Guardian::query()->where('user_id', $user->id)->first();
            if (! $guardian) {
                return collect();
            }

            return $guardian->students->pluck('id');
        }

        return collect();
    }
}
