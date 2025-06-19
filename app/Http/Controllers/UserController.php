<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Users;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\File;

class UserController extends Controller
{
 // update user profile picture



public function updateProfilePicture(Request $request)
{
    $request->validate([
        'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    $user = auth()->user();

    // Delete old profile picture if it exists
    if ($user->profile_picture) {
        $oldPath = base_path('../public_html/' . $user->profile_picture);
        if (File::exists($oldPath)) {
            File::delete($oldPath);
        }
    }

    // Save new profile picture
    $image = $request->file('profile_picture');
    $filename = time() . '_' . $image->getClientOriginalName();
    $destinationPath = base_path('../public_html/profile_pictures');
    $image->move($destinationPath, $filename);

    // Update user record
    $user->profile_picture = 'profile_pictures/' . $filename;
    $user->save();

    return response()->json([
        'message' => 'Profile picture updated successfully.',
        'profile_picture_url' => asset($user->profile_picture),
    ]);
}




    // Get all users
    public function getAllUsers()
    {
        $users = Users::all();
        return response()->json([
            'message' => 'All users fetched successfully.',
            'users' => $users,
        ]);
    }

    // Get user by ID
    public function getUserById($id)
    {
        $user = Users::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        return response()->json([
            'message' => 'User fetched successfully.',
            'user' => $user,
        ]);
    }

    // Update user
    public function updateUser(Request $request, $id)
    {
        $user = Users::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name'       => 'sometimes|required|string|max:255',
            'last_name'        => 'sometimes|required|string|max:255',
            'email'            => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone'            => ['sometimes', 'required', 'regex:/^09\d{8}$/', Rule::unique('users')->ignore($user->id)],
            'password'         => 'nullable|string|min:6|confirmed',
            'driver_liscence'  => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'digital_id'       => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'adress'          => 'nullable|string|max:255',
            'city'             => 'nullable|string|max:100',
            'birth_Date'       => 'nullable|date',
            'role'             => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        if ($request->hasFile('driver_liscence')) {
        $driverLicenseFile = $request->file('driver_liscence');
        $filename = uniqid() . '.' . $request->file('driver_liscence')->getClientOriginalExtension();
        $path = $request->file('driver_liscence')->move(public_path('driver_licences'), $filename);
        $user->driver_license = 'driver_liscence/' . $filename;
        }

        if ($request->hasFile('digital_id')) {
             $digitalIdFile = $request->file('digital_id');
            $filename = uniqid() . '.' . $digitalIdFile->getClientOriginalExtension();
            $digitalIdFile->move(public_path('digital_ids'), $filename);
            $user->digital_id = 'digital_ids/' . $filename;
        }
        
      
            
             
        $user->first_name = $request->first_name ?? $user->first_name;
        $user->last_name = $request->last_name ?? $user->last_name;
        $user->email = $request->email ?? $user->email;
        $user->phone = $request->phone ?? $user->phone;
        $user->adress = $request->adress ?? $user->adress;
        $user->city = $request->city ?? $user->city;
        $user->birth_Date = $request->birth_Date ?? $user->birth_Date;
        $user->role = $request->role ?? $user->role;
        

        $user->save();

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user,
        ]);
    }

    public function verify2FA(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp' => 'required',
        ]);
    
        $storedOtp = Redis::get("2fa:{$request->user_id}");
    
        if (!$storedOtp || $storedOtp !== $request->otp) {
            return response()->json(['error' => 'Invalid or expired OTP'], 400);
        }
    
        $user = Users::find($request->user_id);
        $token = $user->createToken('auth_token')->plainTextToken;
    
        Redis::del("2fa:{$request->user_id}");
    
        return response()->json([
            'message' => 'Two-factor verification successful',
            'token' => $token,
        ]);
    }
    
//toggle two factor code
    public function toggleTwoFactor(Request $request)
        {
            $user = auth()->user();
        
            $user->two_factor_enabled = !$user->two_factor_enabled;
            $user->save();
        
            return response()->json([
                'message' => 'Two-factor authentication ' . ($user->two_factor_enabled ? 'enabled' : 'disabled'),
            ]);
        }
        
//soft delete
public function deleteAccount(Request $request)
{
    $user = auth()->user();
    $user->delete(); 

    return response()->json(['message' => 'Your account has been deleted and is pending permanent removal.']);
}

public function banUser($id)
{
    $user = Users::find($id);

    if (!$user) {
        return response()->json(['message' => 'User not found.'], 404);
    }

    $user->is_banned = true;
    $user->save();

    return response()->json(['message' => 'User has been banned successfully.']);
}

public function unbanUser($id)
{
    $user = Users::find($id);

    if (!$user) {
        return response()->json(['message' => 'User not found.'], 404);
    }

    $user->is_banned = false;
    $user->save();

    return response()->json(['message' => 'User has been unbanned successfully.']);
}

public function deleteUser($id)
{
    $user = Users::find($id);

    if (!$user) {
        return response()->json(['message' => 'User not found.'], 404);
    }

    $user->delete();

    return response()->json(['message' => 'User has been deleted successfully.']);
}


}
