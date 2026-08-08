@extends('admin.layouts.app')

@section('title', 'Unit Master | Admin Panel')
@section('headerTitle', 'Unit Master')
@section('headerSubtitle', 'Create reusable units for BOQ, purchase orders and material records')

@section('content')
    <style>
        .unit-page {
            display: grid;
            gap: 18px;
            max-width: 100%;
            overflow-x: hidden;
        }

        .unit-grid {
            display: grid;
            grid-template-columns: 1fr 2fr auto;
            gap: 14px;
            align-items: end;
        }

        .unit-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .unit-table th,
        .unit-table td {
            border-bottom: 1px solid #d7e3f2;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }

        .unit-table th {
            background: #f8fbff;
            color: #526b91;
            font-size: 12px;
            text-transform: uppercase;
        }

        .unit-table input,
        .unit-table select,
        .unit-grid input {
            width: 100%;
            min-width: 0;
            border: 1px solid #c9d7e8;
            border-radius: 7px;
            padding: 9px 10px;
        }

        .unit-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        @media (max-width: 760px) {
            .unit-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="unit-page">
        <div class="page-head">
            <div>
                <h1>Unit Master</h1>
                <p>Add units once and select them in purchase order item rows.</p>
            </div>
        </div>

        @if (session('success')) <div class="alert-success">{{ session('success') }}</div> @endif
        @if (session('error')) <div class="alert-error">{{ session('error') }}</div> @endif
        @if ($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

        <form class="card form-card unit-grid" method="POST" action="{{ route('admin.units.store') }}">
            @csrf
            <label>Unit Name
                <input name="name" placeholder="CUM, Bag, Nos, RMT" value="{{ old('name') }}" required>
            </label>
            <label>Description
                <input name="description" placeholder="Cubic meter, Bag, Running meter" value="{{ old('description') }}">
            </label>
            <button class="btn" type="submit">Save Unit</button>
        </form>

        <section>
            <h2>Unit List</h2>
            <div class="card table-card">
                <table class="unit-table">
                    <thead>
                        <tr>
                            <th style="width: 22%">Unit</th>
                            <th>Description</th>
                            <th style="width: 16%">Status</th>
                            <th style="width: 18%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($units as $unit)
                            @php($formId = 'unit-form-'.$unit->id)
                            <tr>
                                <td><input form="{{ $formId }}" name="name" value="{{ $unit->name }}" required></td>
                                <td><input form="{{ $formId }}" name="description" value="{{ $unit->description }}"></td>
                                <td>
                                    <select form="{{ $formId }}" name="is_active">
                                        <option value="1" @selected($unit->is_active)>Active</option>
                                        <option value="0" @selected(! $unit->is_active)>Inactive</option>
                                    </select>
                                </td>
                                <td>
                                    <div class="unit-actions">
                                        <form id="{{ $formId }}" method="POST" action="{{ route('admin.units.update', $unit) }}">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                        <button class="btn small" form="{{ $formId }}" type="submit">Update</button>
                                        <form method="POST" action="{{ route('admin.units.destroy', $unit) }}" onsubmit="return confirm('Delete this unit?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger small" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">No unit added yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $units->links('admin.pagination') }}</div>
        </section>
    </div>
@endsection
