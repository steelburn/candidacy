<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Throwable;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'setupCheck', 'createFirstAdmin', 'validateToken']]);
    }

    /**
     * Check if system needs initial setup (no users exist)
     */
    public function setupCheck()
    {
        $userCount = User::count();
        return response()->json([
            'needs_setup' => $userCount === 0,
            'user_count' => $userCount
        ]);
    }

    /**
     * Create the first admin user (only works when no users exist)
     */
    public function createFirstAdmin(Request $request)
    {
        // Only allow if no users exist
        if (User::count() > 0) {
            return response()->json(['error' => 'Setup already completed. Users exist in the system.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        // Assign admin role to first user
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $user->assignRole($adminRole);
        }

        // Auto-login the created admin
        $token = auth('api')->login($user);

        return response()->json([
            'message' => 'Admin user created successfully',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user->load('roles')
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        $token = auth('api')->login($user);

        return $this->respondWithToken($token);
    }

    public function me()
    {
        return response()->json(auth('api')->user()->load('roles'));
    }

    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Validate a JWT token.
     * Used by other services to verify tokens.
     */
    public function validateToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Token is required'], 422);
        }

        try {
            $token = $request->input('token');
            $user = JWTAuth::setToken($token)->authenticate();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            return response()->json([
                'valid' => true,
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token invalid'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token absent or malformed'], 401);
        }
    }

    /**
     * Change the authenticated user's password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth('api')->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'errors' => ['current_password' => ['The current password is incorrect.']]
            ], 422);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }

    /**
     * Switch the authenticated user's active tenant and reissue a JWT
     * embedding the new tenant_id claim.
     */
    public function switchTenant(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth('api')->user();
        $tenantId = (int) $request->tenant_id;

        // Verify membership via tenant-service (unless Super Admin)
        if (!$user->hasRole('admin')) {
            try {
                $response = Http::timeout(5)->post('http://tenant-service:8080/api/internal/verify-membership', [
                    'user_id' => $user->id,
                    'tenant_id' => $tenantId,
                ]);

                if (!$response->successful() || !$response->json('is_member')) {
                    return response()->json(['error' => 'You do not have access to this tenant'], 403);
                }
            } catch (\Exception $e) {
                Log::error("Failed to verify tenant membership: " . $e->getMessage());
                return response()->json(['error' => 'Membership verification failed'], 500);
            }
        }

        // Persist the new active tenant on the user record
        $user->current_tenant_id = $tenantId;
        $user->save();

        // Invalidate the current token and issue a fresh one so the
        // new tenant_id claim is immediately reflected in the JWT.
        auth('api')->invalidate();
        $token = auth('api')->login($user);

        return $this->respondWithToken($token);
    }

    /**
     * Validate the provided JWT and return its payload.
     */
    public function validateToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $payload = JWTAuth::setToken($request->token)->getPayload();
            return response()->json(['valid' => true, 'payload' => $payload]);
        } catch (Exception $e) {
            return response()->json(['valid' => false, 'error' => $e->getMessage()], 401);
        }
    }

    /**
     * Check if the user has the required role.
     */
    public function roleCheck(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'required_role' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth('api')->user();
        $hasRole = $user->roles->contains('name', $request->required_role);

        return response()->json(['access' => $hasRole]);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => auth('api')->user()->load('roles')
        ]);
    }
}
