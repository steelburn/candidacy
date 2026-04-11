<?php

namespace App\Http\Controllers\Api;

use App\Models\Tenant;
use App\Models\TenantUser;
use Shared\Http\Controllers\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TenantMemberController
 * 
 * Handles tenant member management operations.
 */
class TenantMemberController extends BaseApiController
{
    /**
     * List all members of a tenant.
     */
    public function index(Request $request, string $uuid): JsonResponse
    {
        $tenant = Tenant::where('uuid', $uuid)->first();

        if (!$tenant) {
            return $this->notFound('Tenant not found');
        }

        // Check access
        $userId = $request->header('X-User-ID');
        $membership = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$membership) {
            return $this->forbidden('You do not have access to this tenant');
        }

        $members = TenantUser::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get();

        // Enrich with user details from auth-service
        $userIds = $members->pluck('user_id')->unique()->toArray();
        if (!empty($userIds)) {
            try {
                $response = Http::post('http://auth-service:8080/api/internal/users/details', [
                    'user_ids' => $userIds
                ]);

                if ($response->successful()) {
                    $userDetails = collect($response->json('users'))->keyBy('id');
                    $members->transform(function ($member) use ($userDetails) {
                        $user = $userDetails->get($member->user_id);
                        if ($user) {
                            $member->user = [
                                'id' => $user['id'],
                                'name' => $user['name'],
                                'email' => $user['email'],
                                'phone' => $user['phone'] ?? null,
                            ];
                        } else {
                            // Provide valid placeholder if user not found in auth service
                            $member->user = [
                                'id' => $member->user_id,
                                'name' => 'Unknown User',
                                'email' => '',
                            ];
                        }
                        return $member;
                    });
                } else {
                    Log::error("Auth service returned error " . $response->status() . " for user details enrichment");
                    $this->fallbackMemberEnrichment($members);
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch user details for members: " . $e->getMessage());
                $this->fallbackMemberEnrichment($members);
            }
        }

        return $this->success($members);
    }

    /**
     * Add a member to the tenant.
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
            'user_id' => 'required|integer',
            'role' => 'required|string|in:member,interviewer,recruiter,manager,admin',
            'permissions' => 'nullable|array',
        ]);

        // Check user limit
        if ($tenant->hasReachedUserLimit()) {
            return $this->error('User limit reached for this tenant', 403);
        }

        // Check if already a member
        $existing = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $validated['user_id'])
            ->first();

        if ($existing) {
            if ($existing->is_active) {
                return $this->error('User is already a member of this tenant', 409);
            }
            
            // Reactivate
            $existing->update([
                'role' => $validated['role'],
                'permissions' => $validated['permissions'] ?? [],
                'is_active' => true,
                'joined_at' => now(),
            ]);

            return $this->success($existing->fresh());
        }

        $member = TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $validated['user_id'],
            'role' => $validated['role'],
            'permissions' => $validated['permissions'] ?? [],
            'is_active' => true,
            'joined_at' => now(),
        ]);

        return $this->created($member);
    }

    /**
     * Update a member's role.
     */
    public function update(Request $request, string $uuid, int $memberId): JsonResponse
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

        $member = TenantUser::where('id', $memberId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$member) {
            return $this->notFound('Member not found');
        }

        // Cannot change owner's role
        if ($member->isOwner()) {
            return $this->forbidden('Cannot change owner\'s role');
        }

        $validated = $request->validate([
            'role' => 'sometimes|string|in:member,interviewer,recruiter,manager,admin',
            'permissions' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $member->update($validated);

        return $this->success($member->fresh());
    }

    /**
     * Remove a member from the tenant.
     */
    public function destroy(Request $request, string $uuid, int $memberId): JsonResponse
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

        $member = TenantUser::where('id', $memberId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$member) {
            return $this->notFound('Member not found');
        }

        // Cannot remove owner
        if ($member->isOwner()) {
            return $this->forbidden('Cannot remove the owner');
        }

        // Soft deactivate
        $member->update(['is_active' => false]);

        return $this->success(null, 'Member removed successfully');
    }

    /**
     * Leave a tenant (for the current user).
     */
    public function leave(Request $request, string $uuid): JsonResponse
    {
        $tenant = Tenant::where('uuid', $uuid)->first();

        if (!$tenant) {
            return $this->notFound('Tenant not found');
        }

        $userId = $request->header('X-User-ID');
        $membership = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$membership) {
            return $this->notFound('You are not a member of this tenant');
        }

        // Owner cannot leave
        if ($membership->isOwner()) {
            return $this->forbidden('Owner cannot leave. Transfer ownership first.');
        }

        $membership->update(['is_active' => false]);

        return $this->success(null, 'You have left the tenant');
    }

    /**
     * Fallback for member enrichment when auth service is unavailable.
     */
    protected function fallbackMemberEnrichment($members): void
    {
        $members->transform(function ($member) {
            if (!isset($member->user)) {
                $member->user = [
                    'id' => $member->user_id,
                    'name' => 'User ' . $member->user_id,
                    'email' => 'auth-service-unavailable',
                ];
            }
            return $member;
        });
    }
}
