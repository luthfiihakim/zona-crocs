<label>Kategori
    <select name="category_id" required>
        <option value="">Pilih kategori</option>
        @foreach ($categories as $c)
            <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id ?? '') == $c->id)>{{ $c->name }}</option>
        @endforeach
    </select>
</label>
@error('category_id')<p class="err">{{ $message }}</p>@enderror

<label>Nama produk
    <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required>
</label>
@error('name')<p class="err">{{ $message }}</p>@enderror
@error('slug')<p class="err">{{ $message }}</p>@enderror

<label>Deskripsi (opsional)
    <input type="text" name="description" value="{{ old('description', $product->description ?? '') }}">
</label>
@error('description')<p class="err">{{ $message }}</p>@enderror

<label>Harga jual (Rp)
    <input type="number" name="base_price" min="0" step="1" value="{{ old('base_price', $product->base_price ?? '') }}" required>
</label>
@error('base_price')<p class="err">{{ $message }}</p>@enderror

<label>Harga coret (Rp, opsional)
    <input type="number" name="compare_price" min="0" step="1" value="{{ old('compare_price', $product->compare_price ?? '') }}">
</label>
@error('compare_price')<p class="err">{{ $message }}</p>@enderror

<label class="check">
    <input type="checkbox" name="is_active" value="1"
        @checked(session()->hasOldInput() ? old('is_active') : ($product->is_active ?? true))>
    Tampilkan di katalog
</label>

<button class="btn" type="submit">SIMPAN</button>