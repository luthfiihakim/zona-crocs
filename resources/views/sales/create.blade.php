@extends('layouts.app')
@section('title', 'Penjualan Baru')
@section('content')
<div class="toolbar">
    <h1>Penjualan Baru</h1>
    <a class="btn btn-outline" href="{{ route('sales.index') }}">Kembali</a>
</div>

@if ($errors->any())
    <div class="alert bad">
        <ul class="error-list">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('sales.store') }}" class="card">
    @csrf

    <div class="grid-2">
        <label>Pembeli (opsional)
            <select name="customer_id">
                <option value="">Umum (tanpa akun)</option>
                @foreach ($customers as $c)
                    <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>{{ $c->name }} ({{ $c->email }})</option>
                @endforeach
            </select>
        </label>
        <label>Metode pembayaran
            <select name="payment_method" required>
                <option value="">Pilih metode</option>
                @foreach (['cash' => 'Tunai', 'transfer' => 'Transfer', 'qris' => 'QRIS'] as $k => $label)
                    <option value="{{ $k }}" @selected(old('payment_method') === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <label>Catatan (opsional)
        <input type="text" name="notes" value="{{ old('notes') }}" maxlength="500">
    </label>

    <div class="table-wrap">
        <table class="items-table">
            <thead>
                <tr><th>SKU</th><th>Jumlah</th><th>Harga</th><th>Stok</th><th>Subtotal</th><th></th></tr>
            </thead>
            <tbody id="rows"></tbody>
        </table>
    </div>

    <div class="form-actions">
        <button type="button" id="add-row" class="btn btn-outline btn-sm">+ Tambah baris</button>
    </div>

    <div class="total-box">
        <span>Total</span><b id="total">Rp 0</b>
    </div>

    <button class="btn" type="submit">SIMPAN PENJUALAN</button>
    <p class="muted">Harga mengikuti data produk. Stok berkurang otomatis begitu penjualan disimpan.</p>

    <datalist id="skus">
        @foreach ($variants as $v)
            <option value="{{ $v->sku }}">{{ $v->product->name }} · {{ $v->color }} · {{ $v->size->us_label }}</option>
        @endforeach
    </datalist>
</form>

<template id="row-tpl">
    <tr>
        <td class="col-sku"><input list="skus" name="items[__i__][sku]" placeholder="Ketik SKU..." autocomplete="off" class="js-sku"></td>
        <td class="col-num"><input type="number" name="items[__i__][quantity]" min="1" value="1" class="js-qty"></td>
        <td class="js-price">-</td>
        <td class="js-stock">-</td>
        <td class="js-sub">Rp 0</td>
        <td><button type="button" class="btn btn-outline btn-sm js-del">Hapus</button></td>
    </tr>
</template>

<script>
    const catalog = @json($catalog);
    const oldItems = Object.values(@json(old('items', [])));
    const rows = document.getElementById('rows');
    const tpl = document.getElementById('row-tpl').innerHTML;
    const fmt = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
    let idx = 0;

    function recalc() {
        let total = 0;
        rows.querySelectorAll('tr').forEach(tr => {
            const info = catalog[tr.querySelector('.js-sku').value.trim().toUpperCase()];
            const q = Number(tr.querySelector('.js-qty').value) || 0;
            const price = info ? info.price : 0;
            tr.querySelector('.js-price').textContent = info ? fmt(price) : '-';
            tr.querySelector('.js-stock').textContent = info ? (info.po ? 'PO' : info.stock) : '-';
            tr.querySelector('.js-sub').textContent = fmt(q * price);
            total += q * price;
        });
        document.getElementById('total').textContent = fmt(total);
    }

    function addRow(d) {
        d = d || {};
        rows.insertAdjacentHTML('beforeend', tpl.replaceAll('__i__', idx++));
        const tr = rows.lastElementChild;
        if (d.sku) tr.querySelector('.js-sku').value = d.sku;
        if (d.quantity) tr.querySelector('.js-qty').value = d.quantity;
        recalc();
    }

    document.getElementById('add-row').addEventListener('click', () => addRow());
    rows.addEventListener('input', recalc);
    rows.addEventListener('click', e => {
        if (e.target.classList.contains('js-del')) {
            e.target.closest('tr').remove();
            if (!rows.children.length) addRow();
            recalc();
        }
    });

    (oldItems.length ? oldItems : [{}]).forEach(addRow);
</script>
@endsection