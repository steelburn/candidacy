<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\TenantUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternalTenantController extends Controller
{
    /**
     * Verify if a user belongs to a tenant.
     * 
     * POST /internal/verify-membership
     */
    public function verifyMembership(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'tenant_id' => 'required|integer',
        ]);

        $exists = TenantUser::where('user_id', $validated['user_id'])
            ->where('tenant_id', $validated['tenant_id'])
            ->where('is_active', true)
            ->exists();

        return response()->json([
            'is_member' => $exists
        ]);
    }

    /**
     * Get a list of user IDs for a given tenant.
     * 
     * GET /internal/tenants/{tenantId}/user-ids
     */
    public function getTenantUserIds(int $tenantId): JsonResponse
    {
        $userIds = TenantUser::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->pluck('user_id');

        return response()->json([
            'user_ids' => $userIds
        ]);
    }
}
