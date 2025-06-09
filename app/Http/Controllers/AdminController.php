<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Users;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    //Admin see soft delte users
    public function listDeletedUsers()
    {
        $deletedUsers = Users::onlyTrashed()->get();
        return response()->json($deletedUsers);
    }
    public function forceDeleteUser($id)
    {
        $user = Users::onlyTrashed()->where('id', $id)->firstOrFail();
        $user->forceDelete(); 

        return response()->json(['message' => 'User permanently deleted.']);
    }
//admin verify user ststus 
    public function verifyUser(Request $request,$id)
    {
        $request->validate([
            'status' => 'required|in:Approved,Rejected',
        ]);

        $user = Users::findOrFail($id);
        $user->status = $request->status;
        $user->save();

        return response()->json([
            'message' => 'User status updated successfully.',
            'user' => $user,
        ]);
    }

  
public function getUsersByStatus($status)
{
    $validStatuses = ['Pending', 'Approved','Rejected']; 

    if (!in_array($status, $validStatuses)) {
        return response()->json(['error' => 'Invalid status'], 400);
    }

    $users = Users::where('status', $status)->get();

    return response()->json([
        'status' => $status,
        'users' => $users,
    ]);
}

public function usersByType(Request $request): JsonResponse
{
    $type = $request->query('type');

    switch ($type) {
        case 'owner':
            $users = Users::whereHas('cars')->get();
            break;

        case 'renter':
            $users = Users::whereHas('bookings')->get();
            break;

        case 'both':
            $users = Users::whereHas('cars')->whereHas('bookings')->get();
            break;

        default:
            return response()->json([
                'message' => 'Invalid or missing type. Use "owner", "renter", or "both".'
            ], 400);
    }

    return response()->json([
        'users' => $users,
        'type' => $type,
    ]);
}

}
