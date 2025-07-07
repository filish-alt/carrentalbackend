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
use Illuminate\Support\Facades\Log;
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
        'middle_name'      => 'sometimes|required|string|max:255',
        'last_name'        => 'sometimes|required|string|max:255',
        'email'            => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
        'phone'            => ['sometimes', 'required', 'regex:/^(09|07)\d{8}$/', Rule::unique('users')->ignore($user->id)],
        'password'         => 'nullable|string|min:6|confirmed',
        'driver_licence'   => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        'digital_id'       => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        'address'          => 'nullable|string|max:255',
        'city'             => 'nullable|string|max:100',
        'birth_date'       => 'nullable|date',
        'role'             => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Handle file uploads
    if ($request->hasFile('driver_licence')) {
        $file = $request->file('driver_licence');
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('driver_licences'), $filename);
        $user->driver_licence = 'driver_licences/' . $filename;
    }

    if ($request->hasFile('digital_id')) {
        $file = $request->file('digital_id');
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('digital_ids'), $filename);
        $user->digital_id = 'digital_ids/' . $filename;
    }
   Log::info('Incoming request:', $request->all());

    // Update only if present
    foreach ([
        'first_name', 'middle_name', 'last_name', 'email', 'phone',
        'address', 'city', 'birth_date', 'role'
    ] as $field) {
        if ($request->has($field)) {
            $user->$field = $request->$field;
        }
    }

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
    
        // $storedOtp = Redis::get("2fa:{$request->user_id}");
    
        // if (!$storedOtp || $storedOtp !== $request->otp) {
        //     return response()->json(['error' => 'Invalid or expired OTP'], 400);
        // }
    
        //Redis::del("2fa:{$request->user_id}");
    
            $user = Users::find($request->user_id);

                    // Check if OTP matches and not expired
            if (!$user->two_factor_code || $user->two_factor_code !== $request->otp || now()->greaterThan($user->two_factor_expires_at)) {
                return response()->json(['error' => 'Invalid or expired OTP'], 400);
            }

            // Clear OTP fields after successful verification
            $user->two_factor_code = null;
            $user->two_factor_expires_at = null;
            $user->save();
     
          $token = $user->createToken('auth_token')->plainTextToken;
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
