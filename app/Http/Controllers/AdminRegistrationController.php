<?php

namespace App\Http\Controllers;
use App\Http\Request\Admin\AdminRegistrationRequest;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;


class AdminRegistrationController extends Controller
{
     /**
     * Register a new admin user
     *
     * @param AdminRegistrationRequest $request
     * 
     */
     /**
     * @OA\Tag(
     *     name="Admin",
     *     description="Operations related to Admin users"
     * )
     */

    /**
     * @OA\Post(
     *     path="/api/admin/register",
     *     summary="Register a new admin user",
     *     tags={"Admin"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "email", "password"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Admin registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Admin registered successfully."),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Registration failed"
     *     )
     * )
     */
    public function register(AdminRegistrationRequest $request): JsonResponse
    {
        try {
            Log::info('Admin registration attempt', [
                'email' => $request->email,
                'initiated_by' => auth()->id()
            ]);
            
            // Start database transaction
            DB::beginTransaction();
            
            // Create the admin user
            $admin = SuperAdmin::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'hash_password' => Hash::make($request->password),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
            
            DB::commit();
            
            Log::info('Admin registered successfully', [
                'admin_id' => $admin->id,
                'email' => $admin->email,
                'registered_by' => auth()->id()
            ]);
            
              return response()->json([
                'message' => 'Admin registered successfully.',
                'user' => $admin
            ], 201);
            
        } catch (Throwable $e) {
            DB::rollBack();
            
            Log::error('Admin registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $request->email,
                'initiated_by' => auth()->id()
            ]);
            
            return response()->json(['message' => 'Registration failed:'], 500);
        }
 
    }

      /**
     * @OA\Get(
     *     path="/api/admin/list",
     *     summary="Get all registered admin users",
     *     tags={"Admin"},
     *     @OA\Response(
     *         response=200,
     *         description="List of admin users fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="All admin fetched successfully."),
     *             @OA\Property(property="users", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getAllAdmin()
    {
        $users = SuperAdmin::all();
        return response()->json([
            'message' => 'All admin fetched successfully.',
            'users' => $users,
        ]);
    }
}



