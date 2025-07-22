<?php

namespace App\Services;

use App\Models\Users;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class UserService
{
    public function updateProfilePicture($request, $user)
    {
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

        return [
            'message' => 'Profile picture updated successfully.',
            'profile_picture_url' => asset($user->profile_picture),
        ];
    }

    public function getAllUsers()
    {
        return Users::all();
    }

    public function getUserById($id)
    {
        $user = Users::find($id);

        if (!$user) {
            throw new \Exception('User not found.', 404);
        }

        return $user;
    }

    public function updateUser($request, $id)
    {
        $user = Users::find($id);

        if (!$user) {
            throw new \Exception('User not found.', 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'middle_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['sometimes', 'required', 'regex:/^(09|07)\d{8}$/', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'driver_licence' => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
            'digital_id' => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
            'passport' => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'role' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new \Exception(json_encode($validator->errors()), 422);
        }

        // Handle file uploads
        if ($request->hasFile('driver_liscence')) {
            $file = $request->file('driver_liscence');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(base_path('../public_html/driver_licences'), $filename);
            $user->driver_licence = 'driver_licences/' . $filename;
        }

        if ($request->hasFile('digital_id')) {
            $file = $request->file('digital_id');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(base_path('../public_html/digital_ids'), $filename);
            $user->digital_id = 'digital_ids/' . $filename;
        }

        if ($request->hasFile('passport')) {
            $file = $request->file('passport');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(base_path('../public_html/passport'), $filename);
            $user->passport = 'passport/' . $filename;
        }

        Log::info('Incoming request:', $request->all());

        foreach ([
            'first_name', 'middle_name', 'last_name', 'email', 'phone',
            'address', 'city', 'birth_date', 'role'
        ] as $field) {
            if ($request->has($field)) {
                $user->$field = $request->$field;
            }
        }

        $user->save();

        return [
            'message' => 'User updated successfully.',
            'user' => $user,
            'document_urls' => [
                'driver_licence' => $user->driver_licence ? url($user->driver_licence) : null,
                'digital_id' => $user->digital_id ? url($user->digital_id) : null,
                'passport' => $user->passport ? url($user->passport) : null,
            ],
        ];
    }

    public function verify2FA($userId, $otp)
    {
        $user = Users::find($userId);

        // Check if OTP matches and not expired
        if (!$user->two_factor_code || $user->two_factor_code !== $otp || now()->greaterThan($user->two_factor_expires_at)) {
            throw new \Exception('Invalid or expired OTP', 400);
        }

        // Clear OTP fields after successful verification
        $user->two_factor_code = null;
        $user->two_factor_expires_at = null;
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;
        
        return [
            'message' => 'Two-factor verification successful',
            'token' => $token,
        ];
    }

    public function toggleTwoFactor($user)
    {
        $user->two_factor_enabled = !$user->two_factor_enabled;
        $user->save();

        return [
            'message' => 'Two-factor authentication ' . ($user->two_factor_enabled ? 'enabled' : 'disabled'),
        ];
    }

    public function deleteAccount($user)
    {
        $user->delete();
        
        return ['message' => 'Your account has been deleted and is pending permanent removal.'];
    }

    public function banUser($id)
    {
        $user = Users::find($id);

        if (!$user) {
            throw new \Exception('User not found.', 404);
        }

        $user->is_banned = true;
        $user->save();

        return ['message' => 'User has been banned successfully.'];
    }

    public function unbanUser($id)
    {
        $user = Users::find($id);

        if (!$user) {
            throw new \Exception('User not found.', 404);
        }

        $user->is_banned = false;
        $user->save();

        return ['message' => 'User has been unbanned successfully.'];
    }

    public function deleteUser($id)
    {
        $user = Users::find($id);

        if (!$user) {
            throw new \Exception('User not found.', 404);
        }

        $user->delete();

        return ['message' => 'User has been deleted successfully.'];
    }
}
