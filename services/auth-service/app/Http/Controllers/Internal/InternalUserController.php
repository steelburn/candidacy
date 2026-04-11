<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternalUserController extends Controller
{
    /**
     * Get user details for a list of IDs.
     * 
     * POST /internal/users/details
     */
    public function getUserDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer',
        ]);

        $users = User::whereIn('id', $validated['user_ids'])
            ->get(['id', 'name', 'email', 'phone', 'is_active']);

        return response()->json([
            'users' => $users
        ]);
    }
}
