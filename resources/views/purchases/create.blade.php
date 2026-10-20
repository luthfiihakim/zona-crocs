@extends('layouts.app')
@section('title', 'Pembelian Baru')
@section('content')
<div class="toolbar">
    <h1>Pembelian Baru</h1>
    <a class="btn btn-outline" href="{{ route('purchases.index') }}">Kembali</a>
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

<form method="POST" action="{{ route('purchases.store') }}" class="card">
    @csrf

    <div class="grid-2">
        <label>Supplier
            <select name="supplier_id" required>
                <option value="">Pilih supplier</option>
                @foreach ($suppliers as $s)
                    <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Tanggal pembelian
            <input type="date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
        </label>
    </div>

    <label>Catatan (opsional)
        <input type="text" name="notes" value="{{ old('notes') }}" maxlength="500">
    </label>

    <div class="table-wrap">
        <table class="items-table">
            <thead>
                <tr><th>SKU</th><th>Jumlah</th><th>Harga beli / pasang</th><th>Subtotal</th><th></th></tr>
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

    <button class="btn" type="submit">SIMPAN PEMBELIAN</button>
    <p class="muted">Stok varian bertambah otomatis begitu pembelian disimpan.</p>

    <datalist id="skus">
        @foreach ($variants as $v)
            <option value="{{ $v->sku }}">{{ $v->product->name }} · {{ $v->color }} · {{ $v->size->us_label }}</option>
        @endforeach
    </datalist>
</form>

<template id="row-tpl">
    <tr>
        <td class="col-sku"><input list="skus" name="items[__i__][sku]" placeholder="Ketik SKU..." autocomplete="off"></td>
        <td class="col-num"><input type="number" name="items[__i__][quantity]" min="1" value="1" class="js-qty"></td>
        <td class="col-num"><input type="number" name="items[__i__][unit_cost]" min="0" placeholder="0" class="js-cost"></td>
        <td class="js-sub">Rp 0</td>
        <td><button type="button" class="btn btn-outline btn-sm js-del">Hapus</button></td>
    </tr>
</template>

<script>
    const oldItems = Object.values(@json(old('items', [])));
    const rows = document.getElementById('rows');
    const tpl = document.getElementById('row-tpl').innerHTML;
    const fmt = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
    let idx = 0;

    function recalc() {
        let total = 0;
        rows.querySelectorAll('tr').forEach(tr => {
            const q = Number(tr.querySelector('.js-qty').value) || 0;
            const c = Number(tr.querySelector('.js-cost').value) || 0;
            tr.querySelector('.js-sub').textContent = fmt(q * c);
            total += q * c;
        });
        document.getElementById('total').textContent = fmt(total);
    }

    function addRow(d) {
        d = d || {};
        rows.insertAdjacentHTML('beforeend', tpl.replaceAll('__i__', idx++));
        const tr = rows.lastElementChild;
        if (d.sku) tr.querySelector('[name$="[sku]"]').value = d.sku;
        if (d.quantity) tr.querySelector('.js-qty').value = d.quantity;
        if (d.unit_cost) tr.querySelector('.js-cost').value = d.unit_cost;
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