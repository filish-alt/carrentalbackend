<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Users;
class RoleController extends Controller
{

   public function index()
    {
        $permissions = Permission::all(['id', 'name']);
        return response()->json($permissions);
    }

  public function createRole(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $request->name,
                              'guard_name' => 'web',]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json(['message' => 'Role created', 'role' => $role]);
    }
    
 public function assignRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|exists:roles,name',
        ]);

        $user = Users::findOrFail($request->user_id);
        $user->assignRole($request->role);

        return response()->json(['message' => 'Role assigned to user']);
    }
}
