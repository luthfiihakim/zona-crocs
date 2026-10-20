@extends('layouts.app')
@section('title', $product->name)
@if ($poSizes->isNotEmpty())
    @section('announce', 'Ukuran '.$poSizes->implode(', ').' wajib PO')
@endif
@section('content')
<div class="detail">
    <div class="product-img"><span>{{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</span></div>

    <div>
        <small class="muted">{{ $product->category->name }}</small>
        <h1>{{ $product->name }}</h1>
        <p class="price-lg">
            Rp {{ number_format($product->base_price, 0, ',', '.') }}
            @if ($product->compare_price && $product->compare_price > $product->base_price)
                <span class="price-old">Rp {{ number_format($product->compare_price, 0, ',', '.') }}</span>
            @endif
        </p>
        @if ($product->description)
            <p>{{ $product->description }}</p>
        @endif

        @if (auth()->user()->isCustomer())
            <form method="POST" action="{{ route('cart.add') }}">
                @csrf
                <input type="hidden" name="variant_id" id="variant_id">

                <label @if ($colors->count() === 1 && $colors->first() === 'Original') style="display:none" @endif>Warna
                    <select id="color">
                        @foreach ($colors as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </label>

                <label>Ukuran
                    <select id="size"></select>
                </label>
                <p class="muted" id="info"></p>

                <label>Jumlah
                    <input class="qty-input" type="number" name="quantity" min="1" max="20" value="1">
                </label>
                @error('variant_id')<p class="err">{{ $message }}</p>@enderror
                @error('quantity')<p class="err">{{ $message }}</p>@enderror

                <button class="btn" id="add" type="submit">MASUKKAN KE KERANJANG</button>
            </form>
        @else
            <p class="muted">Mode pratinjau toko. Pembelian online hanya untuk akun customer.</p>
        @endif

        <details class="size-guide">
            <summary>Panduan ukuran</summary>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>US</th><th>EU</th><th>Insole</th></tr></thead>
                    <tbody>
                    @foreach ($variants->unique('size') as $v)
                        <tr>
                            <td>{{ $v['size'] }}{{ $v['po'] ? ' (PO)' : '' }}</td>
                            <td>{{ $v['eu'] }}</td>
                            <td>{{ $v['insole'] }} cm</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </div>
</div>

<p class="muted"><a href="{{ route('catalog') }}">Kembali ke katalog</a></p>

<script>
    const variants = @json($variants);
    const colorSel = document.getElementById('color');
    const sizeSel = document.getElementById('size');
    const info = document.getElementById('info');
    const vid = document.getElementById('variant_id');
    const btn = document.getElementById('add');

    function renderSizes() {
        if (!sizeSel) return;
        sizeSel.innerHTML = '';
        variants.filter(v => v.color === colorSel.value).forEach(v => {
            const o = document.createElement('option');
            o.value = v.id;
            o.textContent = v.size + (v.po ? ' (PO)' : v.stock <= 0 ? ' (habis)' : '');
            o.disabled = v.po || v.stock <= 0;
            sizeSel.appendChild(o);
        });
        const first = [...sizeSel.options].find(o => !o.disabled);
        if (first) sizeSel.value = first.value;
        update();
    }

    function update() {
        const opt = sizeSel.selectedOptions[0];
        const v = variants.find(x => String(x.id) === sizeSel.value);
        const ok = v && opt && !opt.disabled;
        vid.value = ok ? v.id : '';
        btn.disabled = !ok;
        info.textContent = ok ? 'Stok tersedia: ' + v.stock : 'Warna ini belum tersedia untuk dibeli.';
    }

    if (colorSel) {
        colorSel.addEventListener('change', renderSizes);
        sizeSel.addEventListener('change', update);
        renderSizes();
    }
</script>
@endsection