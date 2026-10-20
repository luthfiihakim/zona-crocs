<label>Nama supplier
    <input type="text" name="name" value="{{ old('name', $supplier->name ?? '') }}" required autofocus>
</label>
@error('name')<p class="err">{{ $message }}</p>@enderror

<label>Nama kontak (opsional)
    <input type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person ?? '') }}">
</label>
@error('contact_person')<p class="err">{{ $message }}</p>@enderror

<label>No. telepon (opsional)
    <input type="text" name="phone" value="{{ old('phone', $supplier->phone ?? '') }}">
</label>
@error('phone')<p class="err">{{ $message }}</p>@enderror

<label>Email (opsional)
    <input type="email" name="email" value="{{ old('email', $supplier->email ?? '') }}">
</label>
@error('email')<p class="err">{{ $message }}</p>@enderror

<label>Alamat (opsional)
    <input type="text" name="address" value="{{ old('address', $supplier->address ?? '') }}">
</label>
@error('address')<p class="err">{{ $message }}</p>@enderror

<button class="btn" type="submit">SIMPAN</button>