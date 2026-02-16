<?php

namespace App\Http\Controllers\Api;

use App\Models\Tenant;
use App\Models\TenantUser;
use Shared\Http\Controllers\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * TenantController
 * 
 * Handles tenant CRUD operations and management.
 */
class TenantController extends BaseApiController
{
    /**
     * List all tenants the current user has access to.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->header('X-User-ID');
        
        if (!$userId) {
            return $this->error('User ID is required', 401);
        }

        $tenantIds = TenantUser::where('user_id', $userId)
            ->where('is_active', true)
            ->pluck('tenant_id');

        $tenants = Tenant::whereIn('id', $tenantIds)
            ->active()
            ->get();

        return $this->success($tenants);
    }

    /**
     * Create a new tenant.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100|unique:tenants,slug|regex:/^[a-z0-9-]+$/',
            'domain' => 'nullable|string|max:255|unique:tenants,domain',
            'logo_url' => 'nullable|url|max:500',
            'settings' => 'nullable|array',
            'subscription_plan' => 'nullable|string|in:free,starter,professional,enterprise',
        ]);

        $userId = $request->header('X-User-ID');
        
        if (!$userId) {
            return $this->error('User ID is required', 401);
        }

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            
            // Ensure unique slug
            $baseSlug = $validated['slug'];
            $counter = 1;
            while (Tenant::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $baseSlug . '-' . $counter++;
            }
        }

        try {
            DB::beginTransaction();

            $tenant = Tenant::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'domain' => $validated['domain'] ?? null,
                'logo_url' => $validated['logo_url'] ?? null,
                'settings' => $validated['settings'] ?? [],
                'subscription_plan' => $validated['subscription_plan'] ?? 'free',
                'subscription_status' => 'active',
                'owner_id' => $userId,
            ]);

            // Add the creator as owner
            TenantUser::create([
                'tenant_id' => $tenant->id,
                'user_id' => $userId,
                'role' => 'owner',
                'is_active' => true,
                'joined_at' => now(),
            ]);

            DB::commit();

            return $this->created($tenant->fresh()->load('users'));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to create tenant', $e);
        }
    }

    /**
     * Get a specific tenant.
     */
    public function show(Request $request, string $uuid): JsonResponse
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

        return $this->success($tenant->load('users'));
    }

    /**
     * Update a tenant.
     */
    public function update(Request $request, string $uuid): JsonResponse
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
            'name' => 'sometimes|string|max:255',
            'slug' => [
                'sometimes',
                'string',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('tenants', 'slug')->ignore($tenant->id),
            ],
            'domain' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('tenants', 'domain')->ignore($tenant->id),
            ],
            'logo_url' => 'sometimes|nullable|url|max:500',
            'settings' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $tenant->update($validated);

        return $this->success($tenant->fresh());
    }

    /**
     * Delete a tenant (soft delete).
     */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $tenant = Tenant::where('uuid', $uuid)->first();

        if (!$tenant) {
            return $this->notFound('Tenant not found');
        }

        // Only owner can delete
        $userId = $request->header('X-User-ID');
        $membership = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$membership || !$membership->isOwner()) {
            return $this->forbidden('Only the owner can delete a tenant');
        }

        $tenant->delete();

        return $this->success(null, 'Tenant deleted successfully');
    }

    /**
     * Switch the current user's active tenant.
     */
    public function switchTenant(Request $request, string $uuid): JsonResponse
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
            return $this->forbidden('You do not have access to this tenant');
        }

        if (!$tenant->hasActiveSubscription()) {
            return $this->error('This tenant subscription is not active', 403);
        }

        // Return the tenant ID for the auth service to update
        return $this->success([
            'tenant_id' => $tenant->id,
            'tenant_uuid' => $tenant->uuid,
            'tenant_name' => $tenant->name,
            'role' => $membership->role,
        ]);
    }
}
