@extends('admin.layouts.app')

@section('title', 'Material Master | Admin Panel')
@section('headerTitle', 'Material Master')
@section('headerSubtitle', 'Create material list, types, units, and low stock limits')

@section('content')
    <div class="page-header">
        <div>
            <h1>Material Master</h1>
            <p>Keep one clean material list for requests, purchase, and stock.</p>
        </div>
    </div>

    @if (session('success')) <div class="alert-success">{{ session('success') }}</div> @endif
    @if (session('error') || isset($error)) <div class="alert-error">{{ session('error') ?: $error }}</div> @endif
    @if ($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

    <form class="card form-card" method="POST" action="{{ route('admin.materials.store') }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Add Material</h2>
            <div class="form-grid">
                <div class="field">
                    <label>Name</label>
                    <input name="name" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label>Type</label>
                    <input name="material_type" value="{{ old('material_type') }}" placeholder="Civil, Electrical, Plumbing">
                </div>
                <div class="field">
                    <label>Unit</label>
                    <input name="unit" value="{{ old('unit') }}" placeholder="Nos, Bag, Kg, Ltr">
                </div>
                <div class="field">
                    <label>Minimum Stock</label>
                    <input name="minimum_stock" type="number" min="0" step="0.01" value="{{ old('minimum_stock', 0) }}">
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Save Material</button>
                </div>
            </div>
        </section>
    </form>

    <div class="card table-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Unit</th>
                        <th>Minimum</th>
                        <th>Total Stock</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($materials as $material)
                        <tr>
                            @php($formId = 'material-update-'.$material->id)
                            <td><input form="{{ $formId }}" name="name" value="{{ $material->name }}" required></td>
                            <td><input form="{{ $formId }}" name="material_type" value="{{ $material->material_type }}"></td>
                            <td><input form="{{ $formId }}" name="unit" value="{{ $material->unit }}"></td>
                            <td><input form="{{ $formId }}" name="minimum_stock" type="number" min="0" step="0.01" value="{{ number_format($material->minimum_stock, 2, '.', '') }}"></td>
                            <td>{{ number_format((float) $material->stocks_sum_available_quantity, 2) }}</td>
                            <td>
                                <select form="{{ $formId }}" name="is_active">
                                    <option value="1" @selected($material->is_active)>Active</option>
                                    <option value="0" @selected(! $material->is_active)>Inactive</option>
                                </select>
                            </td>
                            <td>
                                <form id="{{ $formId }}" method="POST" action="{{ route('admin.materials.update.post', $material) }}">
                                    @csrf
                                    <button class="btn small" type="submit">Update</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No materials added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="pagination">{{ $materials->links('admin.pagination') }}</div>
@endsection
