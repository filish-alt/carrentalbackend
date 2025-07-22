<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    // update user profile picture
    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $result = $this->userService->updateProfilePicture($request, auth()->user());
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }




    // Get all users
    public function getAllUsers()
    {
        $users = $this->userService->getAllUsers();
        return response()->json([
            'message' => 'All users fetched successfully.',
            'users' => $users,
        ]);
    }

    // Get user by ID
    public function getUserById($id)
    {
        try {
            $user = $this->userService->getUserById($id);
            return response()->json([
                'message' => 'User fetched successfully.',
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    // Update user
     public function update(Request $request, $id)
    {
        try {
            $response = $this->userService->updateUser($request, $id);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function verify2FA(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp' => 'required',
        ]);

        try {
            $response = $this->userService->verify2FA($request->user_id, $request->otp);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function toggleTwoFactor()
    {
        $user = auth()->user();
        return response()->json($this->userService->toggleTwoFactor($user));
    }

    public function deleteAccount()
    {
        $user = auth()->user();
        return response()->json($this->userService->deleteAccount($user));
    }

    public function banUser($id)
    {
        return response()->json($this->userService->banUser($id));
    }

    public function unbanUser($id)
    {
        return response()->json($this->userService->unbanUser($id));
    }

    public function deleteUser($id)
    {
        return response()->json($this->userService->deleteUser($id));
    }


}
