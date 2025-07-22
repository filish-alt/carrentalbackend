<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function listDeletedUsers()
    {
        $users = $this->adminService->listDeletedUsers();
        return response()->json($users);
    }

    public function forceDeleteUser($id)
    {
        $this->adminService->forceDeleteUser($id);
        return response()->json(['message' => 'User permanently deleted.']);
    }

    public function verifyUser(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Approved,Rejected',
        ]);

        $user = $this->adminService->verifyUser($id, $request->status);

        return response()->json([
            'message' => 'User status updated successfully.',
            'user' => $user,
        ]);
    }

    public function getUsersByStatus($status)
    {
        $validStatuses = ['Pending', 'Approved', 'Rejected'];
        if (!in_array($status, $validStatuses)) {
            return response()->json(['error' => 'Invalid status'], 400);
        }

        $users = $this->adminService->getUsersByStatus($status);
        return response()->json(['status' => $status, 'users' => $users]);
    }

    public function usersByType(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $users = $this->adminService->getUsersByType($type);

        if ($users === null) {
            return response()->json([
                'message' => 'Invalid or missing type. Use "owner", "renter", or "both".'
            ], 400);
        }

        return response()->json(['users' => $users, 'type' => $type]);
    }
}
