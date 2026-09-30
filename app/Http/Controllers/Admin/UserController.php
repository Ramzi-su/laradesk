<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListUsersRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(ListUsersRequest $request): View
    {
        $users = User::query()
            ->when($request->validated('role'), fn ($query, string $role) => $query->where('role', $role))
            ->withCount(['tickets', 'assignedTickets'])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
        ]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $role = $request->enum('role', UserRole::class);

        DB::transaction(function () use ($user, $role) {
            // A former agent must not keep tickets that still need work.
            if ($user->isAgent() && $role !== UserRole::Agent) {
                $user->assignedTickets()->open()->update(['agent_id' => null]);
            }

            $user->role = $role;
            $user->save();
        });

        return back()->with('success', __(':name is now :role.', ['name' => $user->name, 'role' => $role->label()]));
    }
}
