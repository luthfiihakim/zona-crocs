<label>Nama
    <input type="text" name="name" value="{{ old('name', $category->name ?? '') }}" required autofocus>
</label>
@error('name')<p class="err">{{ $message }}</p>@enderror
@error('slug')<p class="err">{{ $message }}</p>@enderror
<label>Deskripsi (opsional)
    <input type="text" name="description" value="{{ old('description', $category->description ?? '') }}">
</label>
@error('description')<p class="err">{{ $message }}</p>@enderror
<button class="btn" type="submit">SIMPAN</button>