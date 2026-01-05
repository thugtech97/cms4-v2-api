<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fname' => ['required', 'string', 'max:255'],
            'lname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role'  => ['required', Rule::exists('roles', 'name')],
        ]);

        $user = User::create([
            'fname'    => $validated['fname'],
            'lname'    => $validated['lname'],
            'email'    => $validated['email'],
            'password' => Hash::make('password'), // or generate random
            'is_active'=> true,
        ]);

        // Assign role (Spatie)
        $user->assignRole($validated['role']);

        return response()->json([
            'message' => 'User created successfully',
            'data' => [
                'id'    => $user->id,
                'name'  => $user->full_name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ], 201);
    }

    public function fetch_roles(){
        return response()->json([
            'data' => Role::where('guard_name', 'sanctum')
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);

        $users = User::with('roles')
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($qq) use ($request) {
                    $qq->where('fname', 'like', "%{$request->search}%")
                       ->orWhere('lname', 'like', "%{$request->search}%")
                       ->orWhere('email', 'like', "%{$request->search}%");
                });
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => $users->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                    'status' => $user->is_active ? 'Active' : 'Inactive',
                ];
            }),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function show(User $user)
    {
        return response()->json([
            'data' => [
                'id' => $user->id,
                'fname' => $user->fname,
                'lname' => $user->lname,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'is_active' => $user->is_active,
            ],
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'fname' => ['required', 'string'],
            'lname' => ['required', 'string'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'  => ['required', Rule::exists('roles', 'name')],
            'is_active' => ['boolean'],
        ]);

        $user->update($validated);
        $user->syncRoles([$validated['role']]);

        return response()->json([
            'message' => 'User updated successfully',
        ]);
    }
}
