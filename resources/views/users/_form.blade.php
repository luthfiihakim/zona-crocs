<label>Nama
    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required autofocus>
</label>
@error('name')<p class="err">{{ $message }}</p>@enderror

<label>Email
    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
</label>
@error('email')<p class="err">{{ $message }}</p>@enderror

<label>No. HP (opsional)
    <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}">
</label>
@error('phone')<p class="err">{{ $message }}</p>@enderror

@if (isset($user) && $user->id === auth()->id())
    <input type="hidden" name="role" value="{{ $user->role }}">
    <p class="muted">Role akunmu ({{ $user->role }}) tidak bisa diubah sendiri.</p>
@else
    <label>Role
        <select name="role" required>
            @foreach (['admin' => 'Admin', 'staff' => 'Staff', 'customer' => 'Customer'] as $k => $label)
                <option value="{{ $k }}" @selected(old('role', $user->role ?? 'staff') === $k)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
@endif
@error('role')<p class="err">{{ $message }}</p>@enderror

<label>{{ isset($user) ? 'Password baru (kosongkan jika tidak diubah)' : 'Password' }}
    <input type="password" name="password" {{ isset($user) ? '' : 'required' }} autocomplete="new-password">
</label>
@error('password')<p class="err">{{ $message }}</p>@enderror

<label>Ulangi password
    <input type="password" name="password_confirmation" autocomplete="new-password">
</label>

<button class="btn" type="submit">SIMPAN</button>