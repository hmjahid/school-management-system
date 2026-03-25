<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->can('payments.view');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Payment $payment)
    {
        // Users can view their own payments
        if ($payment->created_by === $user->id) {
            return true;
        }
        
        // Check if user can view any payment
        if ($user->can('payments.view')) {
            return true;
        }
        
        // Check if user can view payments for the paymentable type
        return $user->can("payments.view.{$payment->paymentable_type}");
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('payments.create');
    }
    
    /**
     * Determine whether the user can initiate a payment.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function initiate(User $user)
    {
        // Any authenticated user can initiate a payment
        return true;
    }
    
    /**
     * Determine whether the user can record an offline payment.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function recordOffline(User $user)
    {
        return $user->can('payments.record_offline');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Payment $payment)
    {
        // Only allow updating pending or failed payments
        if (!in_array($payment->payment_status, [Payment::STATUS_PENDING, Payment::STATUS_FAILED])) {
            return false;
        }
        
        // Users can update their own payments if they have the permission
        if ($payment->created_by === $user->id && $user->can('payments.update.own')) {
            return true;
        }
        
        // Check if user can update any payment
        return $user->can('payments.update');
    }
    
    /**
     * Determine whether the user can update the payment status.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function updateStatus(User $user, Payment $payment)
    {
        // Only allow updating status for non-completed payments
        if ($payment->payment_status === Payment::STATUS_COMPLETED) {
            return false;
        }
        
        // Users with permission can update status
        return $user->can('payments.update_status');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Payment $payment)
    {
        // Only allow deleting pending or failed payments
        if (!in_array($payment->payment_status, [Payment::STATUS_PENDING, Payment::STATUS_FAILED])) {
            return false;
        }
        
        // Users can delete their own payments if they have the permission
        if ($payment->created_by === $user->id && $user->can('payments.delete.own')) {
            return true;
        }
        
        // Check if user can delete any payment
        return $user->can('payments.delete');
    }
    
    /**
     * Determine whether the user can refund a payment.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function refund(User $user, Payment $payment)
    {
        // Only allow refunding completed payments
        if ($payment->payment_status !== Payment::STATUS_COMPLETED) {
            return false;
        }
        
        // Check if user has permission to refund payments
        return $user->can('payments.refund');
    }
    
    /**
     * Determine whether the user can export payments.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function export(User $user)
    {
        return $user->can('payments.export');
    }
    
    /**
     * Determine whether the user can view payment reports.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewReports(User $user)
    {
        return $user->can('payments.reports');
    }
    
    /**
     * Determine whether the user can process refunds.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function processRefund(User $user)
    {
        return $user->can('payments.refund');
    }
    
    /**
     * Determine whether the user can view payment gateways.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewGateways(User $user)
    {
        return $user->can('payment_gateways.view');
    }
    
    /**
     * Determine whether the user can manage payment gateways.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function manageGateways(User $user)
    {
        return $user->can('payment_gateways.manage');
    }
}
