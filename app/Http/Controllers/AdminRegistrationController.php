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

      // Get all users
    public function getAllAdmin()
    {
        $users = SuperAdmin::all();
        return response()->json([
            'message' => 'All admin fetched successfully.',
            'users' => $users,
        ]);
    }
}



