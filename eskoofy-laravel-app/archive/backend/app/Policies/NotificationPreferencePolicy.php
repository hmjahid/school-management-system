<?php

namespace App\Policies;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationPreferencePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @return mixed
     */
    public function viewAny(User $user)
    {
        // Only administrators can view all notification preferences
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return mixed
     */
    public function view(User $user, NotificationPreference $preference)
    {
        // Users can view their own preferences, admins can view any
        return $user->id === $preference->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     *
     * @return mixed
     */
    public function create(User $user)
    {
        // Any authenticated user can create notification preferences
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @return mixed
     */
    public function update(User $user, NotificationPreference $preference)
    {
        // Users can update their own preferences, admins can update any
        return $user->id === $preference->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @return mixed
     */
    public function delete(User $user, NotificationPreference $preference)
    {
        // Users can delete their own preferences, admins can delete any
        return $user->id === $preference->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @return mixed
     */
    public function restore(User $user, NotificationPreference $preference)
    {
        // Only administrators can restore preferences
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @return mixed
     */
    public function forceDelete(User $user, NotificationPreference $preference)
    {
        // Only administrators can force delete preferences
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update notification preferences.
     */
    public function updatePreference(User $user, string $type): bool
    {
        // Check if the notification type exists in the config
        $defaultChannels = config("notifications.types.{$type}");

        // Only allow updating preferences for valid notification types
        return ! is_null($defaultChannels);
    }

    /**
     * Determine whether the user can reset notification preferences to defaults.
     */
    public function resetPreference(User $user, string $type): bool
    {
        // Check if the notification type exists in the config
        $defaultChannels = config("notifications.types.{$type}");

        // Only allow resetting preferences for valid notification types
        return ! is_null($defaultChannels);
    }
}
