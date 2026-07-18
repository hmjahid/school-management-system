<?php

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RefundPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('refunds.view_any');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Refund $refund): bool
    {
        // Users can view their own refunds
        if ($user->id === $refund->user_id) {
            return true;
        }

        // Check if user has permission to view any refund
        return $user->can('refunds.view_any');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('refunds.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Refund $refund): bool
    {
        // Only pending refunds can be updated
        if ($refund->status !== 'pending') {
            return false;
        }

        // Check if user has permission to update any refund
        return $user->can('refunds.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Refund $refund): bool
    {
        // Only pending refunds can be deleted
        if ($refund->status !== 'pending') {
            return false;
        }

        // Check if user has permission to delete any refund
        return $user->can('refunds.delete');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('refunds.delete_any');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Refund $refund): bool
    {
        return $user->can('refunds.force_delete');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('refunds.force_delete_any');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Refund $refund): bool
    {
        return $user->can('refunds.restore');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('refunds.restore_any');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Refund $refund): bool
    {
        return $user->can('refunds.replicate');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('refunds.reorder');
    }

    /**
     * Determine whether the user can process the refund.
     */
    public function process(User $user, Refund $refund): bool
    {
        // Only pending refunds can be processed
        if ($refund->status !== 'pending') {
            return false;
        }

        // Check if user has permission to process refunds
        return $user->can('refunds.process');
    }

    /**
     * Determine whether the user can cancel the refund.
     */
    public function cancel(User $user, Refund $refund): bool
    {
        // Only pending refunds can be cancelled
        if ($refund->status !== 'pending') {
            return false;
        }

        // Users can cancel their own refunds or if they have permission
        return $user->id === $refund->user_id || $user->can('refunds.cancel');
    }

    /**
     * Determine whether the user can export refunds.
     */
    public function export(User $user): bool
    {
        return $user->can('refunds.export');
    }

    /**
     * Determine whether the user can view the refund receipt.
     */
    public function viewReceipt(User $user, Refund $refund): bool
    {
        // Users can view their own refund receipts
        if ($user->id === $refund->user_id) {
            return true;
        }

        // Check if user has permission to view any refund receipt
        return $user->can('refunds.view_receipt');
    }
}
