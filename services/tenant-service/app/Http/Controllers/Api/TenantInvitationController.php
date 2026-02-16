<?php

namespace App\Http\Controllers\Api;

use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\TenantUser;
use Shared\Http\Controllers\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TenantInvitationController
 * 
 * Handles tenant invitation operations.
 */
class TenantInvitationController extends BaseApiController
{
    /**
     * List all pending invitations for a tenant.
     */
    public function index(Request $request, string $uuid): JsonResponse
    {
        $tenant = Tenant::where('uuid', $uuid)->first();

        if (!$tenant) {
            return $this->notFound('Tenant not found');
        }

        // Check admin access
        $userId = $request->header('X-User-ID');
        $membership = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$membership || !$membership->isAdmin()) {
            return $this->forbidden('Admin access required');
        }

        $invitations = TenantInvitation::where('tenant_id', $tenant->id)
            ->pending()
            ->get();

        return $this->success($invitations);
    }

    /**
     * Create a new invitation.
     */
    public function store(Request $request, string $uuid): JsonResponse
    {
        $tenant = Tenant::where('uuid', $uuid)->first();

        if (!$tenant) {
            return $this->notFound('Tenant not found');
        }

        // Check admin access
        $userId = $request->header('X-User-ID');
        $membership = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$membership || !$membership->isAdmin()) {
            return $this->forbidden('Admin access required');
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'required|string|in:member,interviewer,recruiter,manager,admin',
            'message' => 'nullable|string|max:500',
            'expires_days' => 'nullable|integer|min:1|max:30',
        ]);

        $email = strtolower($validated['email']);

        // Check if already invited
        $existingInvitation = TenantInvitation::where('tenant_id', $tenant->id)
            ->forEmail($email)
            ->pending()
            ->first();

        if ($existingInvitation) {
            return $this->error('An invitation for this email is already pending', 409);
        }

        // Check if already a member (by checking user service - simplified here)
        // In production, you'd call the auth service to check by email

        // Check user limit
        $currentMemberCount = TenantUser::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();
        $pendingInvitationCount = TenantInvitation::where('tenant_id', $tenant->id)
            ->pending()
            ->count();

        if (($currentMemberCount + $pendingInvitationCount) >= $tenant->max_users) {
            return $this->error('User limit would be exceeded', 403);
        }

        $expiresAt = now()->addDays($validated['expires_days'] ?? 7);

        $invitation = TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'email' => $email,
            'role' => $validated['role'],
            'invited_by' => $userId,
            'message' => $validated['message'] ?? null,
            'expires_at' => $expiresAt,
        ]);

        // TODO: Dispatch event to notification service to send email

        return $this->created($invitation);
    }

    /**
     * Cancel an invitation.
     */
    public function destroy(Request $request, string $uuid, int $invitationId): JsonResponse
    {
        $tenant = Tenant::where('uuid', $uuid)->first();

        if (!$tenant) {
            return $this->notFound('Tenant not found');
        }

        // Check admin access
        $userId = $request->header('X-User-ID');
        $membership = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$membership || !$membership->isAdmin()) {
            return $this->forbidden('Admin access required');
        }

        $invitation = TenantInvitation::where('id', $invitationId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$invitation) {
            return $this->notFound('Invitation not found');
        }

        $invitation->delete();

        return $this->success(null, 'Invitation cancelled');
    }

    /**
     * Accept an invitation by token.
     * This is a public route - no tenant access check needed.
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = TenantInvitation::findByToken($token);

        if (!$invitation) {
            return $this->notFound('Invitation not found');
        }

        if (!$invitation->isValid()) {
            if ($invitation->isAccepted()) {
                return $this->error('This invitation has already been accepted', 410);
            }
            if ($invitation->isExpired()) {
                return $this->error('This invitation has expired', 410);
            }
        }

        $userId = $request->header('X-User-ID');
        
        if (!$userId) {
            // Return invitation details for unauthenticated users
            // They'll need to register/login first
            return $this->success([
                'tenant_name' => $invitation->tenant->name,
                'role' => $invitation->role,
                'message' => $invitation->message,
                'requires_auth' => true,
            ]);
        }

        // Check if already a member
        $existing = TenantUser::where('tenant_id', $invitation->tenant_id)
            ->where('user_id', $userId)
            ->first();

        if ($existing && $existing->is_active) {
            $invitation->markAsAccepted();
            return $this->success([
                'tenant' => $invitation->tenant,
                'message' => 'You are already a member of this tenant',
            ]);
        }

        // Create or reactivate membership
        if ($existing) {
            $existing->update([
                'role' => $invitation->role,
                'is_active' => true,
                'joined_at' => now(),
            ]);
        } else {
            TenantUser::create([
                'tenant_id' => $invitation->tenant_id,
                'user_id' => $userId,
                'role' => $invitation->role,
                'is_active' => true,
                'joined_at' => now(),
            ]);
        }

        $invitation->markAsAccepted();

        return $this->success([
            'tenant' => $invitation->tenant,
            'role' => $invitation->role,
            'message' => 'You have joined the tenant successfully',
        ]);
    }

    /**
     * Get invitation details by token (public).
     */
    public function show(string $token): JsonResponse
    {
        $invitation = TenantInvitation::findByToken($token);

        if (!$invitation) {
            return $this->notFound('Invitation not found');
        }

        return $this->success([
            'tenant_name' => $invitation->tenant->name,
            'tenant_logo' => $invitation->tenant->logo_url,
            'role' => $invitation->role,
            'message' => $invitation->message,
            'is_valid' => $invitation->isValid(),
            'is_expired' => $invitation->isExpired(),
            'is_accepted' => $invitation->isAccepted(),
            'expires_at' => $invitation->expires_at,
        ]);
    }
}
