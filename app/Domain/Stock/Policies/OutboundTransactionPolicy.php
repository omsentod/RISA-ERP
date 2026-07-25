<?php

namespace App\Domain\Stock\Policies;

use App\Domain\Stock\Models\OutboundTransaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OutboundTransactionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_outbound::transaction');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OutboundTransaction $outboundTransaction): bool
    {
        return $user->can('view_outbound::transaction');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_outbound::transaction');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OutboundTransaction $outboundTransaction): bool
    {
        return $user->can('update_outbound::transaction');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OutboundTransaction $outboundTransaction): bool
    {
        return $user->can('delete_outbound::transaction');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_outbound::transaction');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, OutboundTransaction $outboundTransaction): bool
    {
        return $user->can('force_delete_outbound::transaction');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_outbound::transaction');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, OutboundTransaction $outboundTransaction): bool
    {
        return $user->can('restore_outbound::transaction');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_outbound::transaction');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, OutboundTransaction $outboundTransaction): bool
    {
        return $user->can('replicate_outbound::transaction');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_outbound::transaction');
    }
}
