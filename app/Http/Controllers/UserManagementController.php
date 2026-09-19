<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Society;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
        ]);

        $users = User::with('society')
            ->when($validated['q'] ?? null, function ($query, string $term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', '%'.$term.'%')
                        ->orWhere('email', 'like', '%'.$term.'%');
                });
            })
            ->when($validated['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->orderBy('name')
            ->get();

        return view('users.index', ['users' => $users, 'roles' => UserRole::cases()]);
    }

    public function edit(User $user)
    {
        return view('users.edit', [
            'managedUser' => $user->load('society'),
            'roles' => UserRole::cases(),
            'societies' => Society::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
            'society_id' => [
                Rule::requiredIf($request->input('role') === UserRole::Organizer->value),
                'nullable',
                Rule::exists('societies', 'id')->where('is_active', true),
            ],
        ]);

        if ($request->user()->is($user) && $validated['role'] !== UserRole::Administrator->value) {
            throw ValidationException::withMessages([
                'role' => 'You cannot remove your own administrator access.',
            ]);
        }

        $user->update([
            'role' => $validated['role'],
            'society_id' => $validated['role'] === UserRole::Organizer->value
                ? $validated['society_id']
                : null,
        ]);

        return redirect()->route('users.index')->with('success', 'User access updated successfully.');
    }
}
