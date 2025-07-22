<?php

namespace App\Services;

use App\Models\SuperAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminRegistrationService
{
    public function adminRegister(array $data)
    {
        try {
            Log::info('Admin registration attempt', [
                'email' => $data['email'],
                'initiated_by' => auth()->id()
            ]);
            
            // Start database transaction
            DB::beginTransaction();
            
            // Create the admin user
            $admin = SuperAdmin::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'hash_password' => Hash::make($data['password']),
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
            
            return [
                'message' => 'Admin registered successfully.',
                'user' => $admin
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Admin registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $data['email'],
                'initiated_by' => auth()->id()
            ]);
            
            throw $e;
        }
    }
    
    public function getAllAdmins()
    {
        return SuperAdmin::all();
    }
}
