@props(['dari', 'sampai', 'action'])

<form method="GET" action="{{ $action }}" class="filter-bar">
    <div class="field">
        <label for="dari">Dari Tanggal</label>
        <input id="dari" type="date" name="dari" value="{{ $dari->format('Y-m-d') }}">
    </div>
    <div class="field">
        <label for="sampai">Sampai Tanggal</label>
        <input id="sampai" type="date" name="sampai" value="{{ $sampai->format('Y-m-d') }}">
    </div>
    <button type="submit" class="btn btn-sm">Terapkan</button>
</form>
