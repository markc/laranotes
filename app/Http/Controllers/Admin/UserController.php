<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->orderBy('id')
            ->paginate(25)
            ->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role?->value,
                'email_verified_at' => $u->email_verified_at?->toIso8601String(),
                'created_at' => $u->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'roles' => array_map(fn (Role $r) => $r->value, Role::cases()),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(array_map(fn (Role $r) => $r->value, Role::cases()))],
        ]);

        $newRole = Role::from($data['role']);
        $actor = $request->user();

        DB::transaction(function () use ($user, $newRole, $actor) {
            if ($actor->id === $user->id && $newRole !== Role::Admin) {
                throw ValidationException::withMessages([
                    'role' => 'You cannot demote your own admin account.',
                ]);
            }

            if ($user->role === Role::Admin && $newRole !== Role::Admin) {
                $adminCount = User::where('role', Role::Admin->value)
                    ->lockForUpdate()
                    ->count();
                if ($adminCount <= 1) {
                    throw ValidationException::withMessages([
                        'role' => 'Cannot demote the last remaining admin.',
                    ]);
                }
            }

            $user->update(['role' => $newRole->value]);
        });

        return back()->with('success', "Role updated to {$newRole->value}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        DB::transaction(function () use ($user, $actor) {
            if ($actor->id === $user->id) {
                throw ValidationException::withMessages([
                    'user' => 'You cannot delete your own account from the admin panel.',
                ]);
            }

            if ($user->role === Role::Admin) {
                $adminCount = User::where('role', Role::Admin->value)
                    ->lockForUpdate()
                    ->count();
                if ($adminCount <= 1) {
                    throw ValidationException::withMessages([
                        'user' => 'Cannot delete the last remaining admin.',
                    ]);
                }
            }

            $user->delete();
        });

        return back()->with('success', 'User deleted.');
    }
}
