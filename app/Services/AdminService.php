<?php

namespace App\Services;

use App\Models\Users;
use Illuminate\Http\Request;

class AdminService
{
    public function listDeletedUsers()
    {
        return Users::onlyTrashed()->get();
    }

    public function forceDeleteUser($id)
    {
        $user = Users::onlyTrashed()->where('id', $id)->firstOrFail();
        $user->forceDelete();
        return true;
    }

    public function verifyUser($id, $status)
    {
        $user = Users::findOrFail($id);
        $user->status = $status;
        $user->save();
        return $user;
    }

    public function getUsersByStatus($status)
    {
        return Users::where('status', $status)->get();
    }

    public function getUsersByType($type)
    {
        switch ($type) {
            case 'owner':
                return Users::whereHas('cars')->get();

            case 'renter':
                return Users::whereHas('bookings')->get();

            case 'both':
                return Users::whereHas('cars')->whereHas('bookings')->get();

            default:
                return null;
        }
    }
}
