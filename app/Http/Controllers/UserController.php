<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $role = $request->query('role');
        $status = $request->query('status');

        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")->orWhere('email', 'ilike', "%{$q}%");
                });
            })
            ->when($role, fn ($query) => $query->where('role', $role))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', compact('users', 'q', 'role', 'status'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(UserRequest $request)
    {
        $data = $request->validated();

        $user = new User(Arr::only($data, ['name', 'email', 'phone', 'password']));
        $user->role = $data['role'];
        $user->email_verified_at = now();
        $user->save();

        return redirect()->route('users.index')->with('success', 'Akun berhasil dibuat.');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(UserRequest $request, User $user)
    {
        $data = $request->validated();

        if ($user->id === $request->user()->id && $data['role'] !== $user->role) {
            return back()->withInput()->with('error', 'Kamu tidak bisa mengubah role akunmu sendiri.');
        }

        $user->fill(Arr::only($data, ['name', 'email', 'phone']));
        $user->role = $data['role'];

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function toggle(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Kamu tidak bisa menonaktifkan akunmu sendiri.');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('success', $user->is_active
            ? 'Akun diaktifkan kembali.'
            : 'Akun dinonaktifkan. Sesi login yang sedang berjalan akan berakhir.');
    }
}