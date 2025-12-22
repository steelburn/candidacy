<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * List all available roles
     */
    public function index()
    {
        $roles = Role::all();
        return response()->json($roles);
    }

    /**
     * Get a specific role with its users
     */
    public function show($id)
    {
        $role = Role::with('users')->findOrFail($id);
        return response()->json($role);
    }

    /**
     * Assign a role to a user
     */
    public function assignRole(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $role = Role::where('id', $request->role_id)
            ->orWhere('name', $request->role_name)
            ->first();

        if (!$role) {
            return response()->json(['error' => 'Role not found'], 404);
        }

        $user->assignRole($role);

        return response()->json([
            'message' => 'Role assigned successfully',
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Remove a role from a user
     */
    public function removeRole($userId, $roleId)
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);

        // Prevent removing the last admin
        if ($role->name === 'admin') {
            $adminCount = User::whereHas('roles', function($q) {
                $q->where('name', 'admin');
            })->count();
            
            if ($adminCount <= 1 && $user->hasRole('admin')) {
                return response()->json([
                    'error' => 'Cannot remove the last admin role'
                ], 403);
            }
        }

        $user->removeRole($role);

        return response()->json([
            'message' => 'Role removed successfully',
            'user' => $user->load('roles')
        ]);
    }
}
