<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Block;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        return Inertia::render('Dashboard/Users/Index', [
            'user'  => $currentUser,
            'users' => User::with('assignedBlocks')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        return Inertia::render('Dashboard/Users/Create', [
            'user'   => $currentUser,
            'blocks' => Block::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:8|confirmed',
            'role'         => 'required|in:admin,field_officer',
            'phone_number' => 'nullable|string|max:20',
        ]);

        User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'password'     => Hash::make($validated['password']),
            'role'         => $validated['role'],
            'phone_number' => $validated['phone_number'] ?? null,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Petugas berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        return Inertia::render('Dashboard/Users/Edit', [
            'user'       => $currentUser,
            'editedUser' => $user,
            'blocks'     => Block::where('is_active', true)->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'email'        => "required|email|unique:users,email,{$user->id}",
            'role'         => 'required|in:admin,field_officer',
            'phone_number' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'Data petugas berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        return back()->with('success', 'Password berhasil direset.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Petugas berhasil dihapus.');
    }
}
