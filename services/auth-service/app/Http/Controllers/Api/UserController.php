<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();
        $tenantId = $request->header('X-Tenant-ID');

        $query = User::with('roles')->latest();

        // If not a Super Admin, filter by tenant membership
        if (!$user->hasRole('admin')) {
            if (!$tenantId) {
                return response()->json(['error' => 'Tenant context required'], 400);
            }

            try {
                // Fetch member IDs from tenant-service
                $response = Http::get("http://tenant-service:8080/api/internal/tenants/{$tenantId}/user-ids");
                
                if ($response->successful()) {
                    $memberIds = $response->json('user_ids');
                    $query->whereIn('id', $memberIds);
                } else {
                    return response()->json(['error' => 'Failed to fetch tenant members'], 500);
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch tenant members: " . $e->getMessage());
                return response()->json(['error' => 'Membership verification failed'], 500);
            }
        }

        $users = $query->get();
        return response()->json($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'department' => $request->department,
            'position' => $request->position,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json($user, 201);
    }

    /**
     * Display the specified user.
     */
    public function show(string $id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'email', 'department', 'position', 'is_active']);
        
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json($user);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting yourself
        if (auth('api')->id() == $id) {
            return response()->json(['error' => 'Cannot delete your own account'], 403);
        }
        
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
