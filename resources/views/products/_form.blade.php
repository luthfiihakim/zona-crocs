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

@php($photoColors = isset($product) ? $product->variants->pluck('color')->unique()->sort()->values() : collect())
@php($photoErrors = collect($errors->messages())->filter(fn ($m, $k) => preg_match('/^(photos|photo_colors|images)(\.|$)/', $k))->flatten()->unique())

<fieldset class="photo-box">
    <legend>Foto produk</legend>
    @foreach ($photoErrors as $msg)
        <p class="err">{{ $msg }}</p>
    @endforeach

    @if (isset($product) && $product->images->isNotEmpty())
        <p class="muted" style="margin-top:0">Foto yang sudah ada. Perubahan baru berlaku setelah klik SIMPAN.</p>
        <div class="photo-grid">
            @foreach ($product->images as $img)
                <div class="photo-card">
                    <img src="{{ $img->url }}" alt="Foto {{ $product->name }}" loading="lazy">
                    <label>Untuk warna
                        <select name="images[{{ $img->id }}][color]">
                            <option value="">Semua warna (umum)</option>
                            @foreach ($photoColors as $c)
                                <option value="{{ $c }}" @selected(old("images.{$img->id}.color", $img->color) === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Urutan
                        <input type="number" name="images[{{ $img->id }}][sort_order]" min="0" max="99"
                               value="{{ old("images.{$img->id}.sort_order", $img->sort_order) }}">
                    </label>
                    <label>Ganti foto
                        <input type="file" name="images[{{ $img->id }}][file]" accept="image/jpeg,image/png,image/webp">
                    </label>
                    <label class="check">
                        <input class="del" type="checkbox" name="images[{{ $img->id }}][delete]" value="1"> Hapus foto ini
                    </label>
                </div>
            @endforeach
        </div>
    @endif

    <label style="margin-top:14px">Tambah foto baru
        <input type="file" id="photo-input" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
    </label>
    <p class="muted" style="margin:-8px 0 10px">
        Bisa memilih berkali-kali: setiap pilihan ditambahkan ke daftar di bawah. Maks. 8 foto, 3 MB per foto, JPG, PNG, atau WEBP.
    </p>
    <div id="photo-list" class="new-photo-list"></div>
    <small id="photo-count" class="muted"></small>
</fieldset>

<script>
(() => {
    const input = document.getElementById('photo-input');
    const list = document.getElementById('photo-list');
    const counter = document.getElementById('photo-count');
    const colors = @json($photoColors);
    const MAX = 8;
    let dt = new DataTransfer();
    const chosenColor = [];
    const keyOf = f => f.name + '|' + f.size + '|' + f.lastModified;

    input.addEventListener('change', () => {
        const have = new Set([...dt.files].map(keyOf));
        [...input.files].forEach(f => {
            if (dt.files.length < MAX && !have.has(keyOf(f))) {
                dt.items.add(f);
                chosenColor.push('');
                have.add(keyOf(f));
            }
        });
        input.files = dt.files;
        render();
    });

    input.form.addEventListener('submit', () => { input.files = dt.files; });

    function render() {
        list.innerHTML = '';
        [...dt.files].forEach((f, i) => {
            const row = document.createElement('div');
            row.className = 'new-photo';
            const img = document.createElement('img');
            img.alt = '';
            img.src = URL.createObjectURL(f);
            img.onload = () => URL.revokeObjectURL(img.src);
            const info = document.createElement('div');
            info.className = 'np-info';
            const name = document.createElement('b');
            name.textContent = f.name;
            const size = document.createElement('small');
            size.textContent = (f.size / 1048576).toFixed(2) + ' MB' + (f.size > 3145728 ? ' (terlalu besar, maks. 3 MB)' : '');
            info.append(name, size);
            row.append(img, info);

            if (colors.length) {
                const sel = document.createElement('select');
                sel.name = 'photo_colors[]';
                sel.add(new Option('Semua warna (umum)', ''));
                colors.forEach(c => sel.add(new Option(c, c)));
                sel.value = chosenColor[i];
                sel.addEventListener('change', () => { chosenColor[i] = sel.value; });
                row.append(sel);
            }

            const rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'btn btn-outline btn-sm';
            rm.textContent = 'Buang';
            rm.addEventListener('click', () => {
                const next = new DataTransfer();
                [...dt.files].forEach((x, j) => { if (j !== i) next.items.add(x); });
                dt = next;
                chosenColor.splice(i, 1);
                input.files = dt.files;
                render();
            });
            row.append(rm);
            list.append(row);
        });
        counter.textContent = dt.files.length ? dt.files.length + ' foto baru akan ditambahkan saat SIMPAN.' : '';
    }
})();
</script>

<button class="btn" type="submit">SIMPAN</button>