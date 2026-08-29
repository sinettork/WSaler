<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    /**
     * Request approval for a given approvable model.
     *
     * @param  object  $approvable   The model needing approval (e.g., PurchaseOrder)
     * @param  User    $requestedBy  The user requesting approval
     * @param  string  $requiredLevel The required approval level (e.g., 'manager', 'admin')
     * @param  array   $metadata     Optional metadata (amount, risk_level, etc.)
     * @param  string  $notes        Optional notes
     * @return Approval
     */
    public function requestApproval(object $approvable, User $requestedBy, string $requiredLevel = 'manager', array $metadata = [], ?string $notes = null): Approval
    {
        // Check if there's already a pending approval for this item
        $existing = Approval::where('approvable_type', get_class($approvable))
            ->where('approvable_id', $approvable->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            throw new \RuntimeException('A pending approval already exists for this item.');
        }

        // Determine approver based on required level
        $approver = $this->determineApprover($requiredLevel);

        return DB::transaction(function () use ($approvable, $requestedBy, $requiredLevel, $metadata, $notes, $approver) {
            $approval = Approval::create([
                'approvable_type' => get_class($approvable),
                'approvable_id'   => $approvable->id,
                'requested_by'    => $requestedBy->id,
                'approver_id'     => $approver?->id,
                'status'          => 'pending',
                'required_level'  => $requiredLevel,
                'notes'           => $notes,
                'metadata'        => $metadata,
                'requested_at'    => now(),
            ]);

            return $approval->fresh(['requestedBy', 'approver']);
        });
    }

    /**
     * Approve a pending approval request.
     */
    public function approve(Approval $approval, User $approver, ?string $notes = null): Approval
    {
        if ($approval->status !== 'pending') {
            throw new \RuntimeException('Only pending approvals can be approved.');
        }

        if ($approval->approver_id !== $approver->id && !$approver->hasRole(['super_admin', 'administrator'])) {
            throw new \RuntimeException('You are not authorized to approve this request.');
        }

        $approval->update([
            'status'      => 'approved',
            'approver_id' => $approver->id,
            'decided_at'  => now(),
            'notes'       => $notes ?? $approval->notes,
        ]);

        // Trigger any post-approval logic on the approvable
        $this->triggerPostApproval($approval->approvable);

        return $approval->fresh(['requestedBy', 'approver']);
    }

    /**
     * Reject a pending approval request.
     */
    public function reject(Approval $approval, User $approver, ?string $notes = null): Approval
    {
        if ($approval->status !== 'pending') {
            throw new \RuntimeException('Only pending approvals can be rejected.');
        }

        if ($approval->approver_id !== $approver->id && !$approver->hasRole(['super_admin', 'administrator'])) {
            throw new \RuntimeException('You are not authorized to reject this request.');
        }

        $approval->update([
            'status'      => 'rejected',
            'approver_id' => $approver->id,
            'decided_at'  => now(),
            'notes'       => $notes ?? $approval->notes,
        ]);

        return $approval->fresh(['requestedBy', 'approver']);
    }

    /**
     * Cancel a pending approval (by the requester).
     */
    public function cancel(Approval $approval, User $user): Approval
    {
        if ($approval->status !== 'pending') {
            throw new \RuntimeException('Only pending approvals can be cancelled.');
        }

        if ($approval->requested_by !== $user->id && !$user->hasRole(['super_admin', 'administrator'])) {
            throw new \RuntimeException('You are not authorized to cancel this request.');
        }

        $approval->update([
            'status'     => 'cancelled',
            'decided_at' => now(),
        ]);

        return $approval->fresh(['requestedBy', 'approver']);
    }

    /**
     * Get pending approvals for a user (where they are the approver).
     */
    public function getPendingForApprover(User $approver): Collection
    {
        return Approval::with(['approvable', 'requestedBy'])
            ->where('approver_id', $approver->id)
            ->where('status', 'pending')
            ->orderBy('requested_at')
            ->get();
    }

    /**
     * Get all approvals requested by a user.
     */
    public function getRequestedBy(User $user): Collection
    {
        return Approval::with(['approvable', 'approver'])
            ->where('requested_by', $user->id)
            ->orderByDesc('requested_at')
            ->get();
    }

    /**
     * Get approval history for a specific approvable.
     */
    public function getHistoryForApproved(object $approvable): Collection
    {
        return Approval::with(['requestedBy', 'approver'])
            ->where('approvable_type', get_class($approvable))
            ->where('approvable_id', $approvable->id)
            ->orderByDesc('requested_at')
            ->get();
    }

    /**
     * Determine the appropriate approver based on required level.
     * In a real system, this might involve branch/warehouse scoping.
     */
    private function determineApprover(string $requiredLevel): ?User
    {
        // For now, return the first user with the required role
        // In a real implementation, this would consider branch/warehouse scoping
        return User::whereHas('roles', function ($q) use ($requiredLevel) {
            $q->where('name', $requiredLevel);
        })->first();
    }

    /**
     * Trigger post-approval logic on the approvable model.
     * Models can implement an `onApproved()` method.
     */
    private function triggerPostApproval($approvable): void
    {
        if (method_exists($approvable, 'onApproved')) {
            $approvable->onApproved();
        }
    }
}
