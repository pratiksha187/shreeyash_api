@extends('admin.layouts.app')

@section('title', 'Supplier Master | Admin Panel')
@section('headerTitle', 'Supplier Master')
@section('headerSubtitle', 'Create supplier list for purchase orders and vendor records')

@section('content')
    <style>
        .supplier-page {
            display: grid;
            gap: 18px;
            max-width: 100%;
            overflow-x: hidden;
        }

        .supplier-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px 18px;
        }

        .supplier-grid .wide {
            grid-column: span 2;
        }

        .supplier-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            min-width: 1220px;
        }

        .supplier-table th,
        .supplier-table td {
            border-bottom: 1px solid #d7e3f2;
            padding: 10px;
            vertical-align: top;
        }

        .supplier-table th {
            background: #f8fbff;
            color: #526b91;
            font-size: 12px;
            text-transform: uppercase;
            text-align: left;
        }

        .supplier-table input,
        .supplier-table select,
        .supplier-table textarea,
        .supplier-grid input,
        .supplier-grid select,
        .supplier-grid textarea {
            width: 100%;
            min-width: 0;
            border: 1px solid #c9d7e8;
            border-radius: 7px;
            padding: 9px 10px;
        }

        .supplier-table textarea,
        .supplier-grid textarea {
            min-height: 62px;
            resize: vertical;
        }

        .supplier-actions {
            display: grid;
            gap: 8px;
        }

        .supplier-actions .btn {
            width: 100%;
        }

        .supplier-statutory-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px 18px;
            margin-top: 18px;
        }

        .supplier-statutory-stack {
            display: grid;
            gap: 8px;
        }

        @media (max-width: 1100px) {
            .supplier-grid,
            .supplier-statutory-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .supplier-grid,
            .supplier-statutory-grid {
                grid-template-columns: 1fr;
            }

            .supplier-grid .wide {
                grid-column: span 1;
            }
        }
    </style>

    <div class="supplier-page">
        <div class="page-head">
            <div>
                <h1>Supplier Master</h1>
                <p>Use this master in Purchase Orders to auto-fill supplier address, GST, TDS and statutory details.</p>
            </div>
        </div>

        @if (session('success')) <div class="alert-success">{{ session('success') }}</div> @endif
        @if (session('error')) <div class="alert-error">{{ session('error') }}</div> @endif
        @if ($errors->any()) <div class="alert-error">{{ $errors->first() }}</div> @endif

        <form class="card form-card" method="POST" action="{{ route('admin.suppliers.store') }}">
            @csrf
            <section class="form-section">
                <h2 class="section-title">Add Supplier</h2>
                <div class="supplier-grid">
                    <label>Name<input name="name" value="{{ old('name') }}" required></label>
                    <label>Contact Person<input name="contact_person" value="{{ old('contact_person') }}"></label>
                    <label>Mobile<input name="mobile" value="{{ old('mobile') }}"></label>
                    <label>Email<input name="email" type="email" value="{{ old('email') }}"></label>
                    <label>GSTIN<input name="gstin" value="{{ old('gstin') }}"></label>
                    <label>Default Dispatch<input name="default_dispatched_through" value="{{ old('default_dispatched_through') }}"></label>
                    <label>Default Destination<input name="default_destination" value="{{ old('default_destination') }}"></label>
                    <label class="wide">Address<textarea name="address">{{ old('address') }}</textarea></label>
                    <label class="wide">Default Terms<textarea name="default_terms">{{ old('default_terms', 'Material Supply: Supplier shall supply and deliver material as per agreed specification and quantity.'."\n".'Payment Terms: Payment shall be made after material receipt as per agreed terms.') }}</textarea></label>
                </div>
            </section>
            <section class="form-section">
                <h2 class="section-title">GST / Statutory</h2>
                <div class="supplier-statutory-grid">
                    <label>GST Registration
                        <select name="gst_registration_type">
                            @foreach (['' => 'Select type', 'regular' => 'Regular', 'composition' => 'Composition', 'unregistered' => 'Unregistered'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('gst_registration_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>GST Return Status
                        <select name="gst_return_status">
                            @foreach (['' => 'Select status', 'pending' => 'Pending', 'filed' => 'Filed', 'not_applicable' => 'Not Applicable'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('gst_return_status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>TDS Section<input name="tds_section" value="{{ old('tds_section') }}" placeholder="194C, 194J"></label>
                    <label>TDS %<input name="tds_percent" type="number" min="0" max="100" step="0.01" value="{{ old('tds_percent') }}"></label>
                    <label>E-Invoice
                        <select name="e_invoice_applicable">
                            <option value="0" @selected(old('e_invoice_applicable', '0') === '0')>No</option>
                            <option value="1" @selected(old('e_invoice_applicable') === '1')>Yes</option>
                        </select>
                    </label>
                    <label>E-Way Bill
                        <select name="e_way_bill_applicable">
                            <option value="0" @selected(old('e_way_bill_applicable', '0') === '0')>No</option>
                            <option value="1" @selected(old('e_way_bill_applicable') === '1')>Yes</option>
                        </select>
                    </label>
                    <label>Vendor Reconciliation
                        <select name="vendor_reconciliation_status">
                            @foreach (['' => 'Select status', 'pending' => 'Pending', 'matched' => 'Matched', 'mismatch' => 'Mismatch', 'not_required' => 'Not Required'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('vendor_reconciliation_status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Auditor Export Note<textarea name="auditor_export_note">{{ old('auditor_export_note') }}</textarea></label>
                </div>
            </section>
            <button class="btn" type="submit">Save Supplier</button>
        </form>

        <section>
            <h2>Supplier List</h2>
            <div class="card table-card">
                <table class="supplier-table">
                    <thead>
                        <tr>
                            <th style="width: 16%">Name</th>
                            <th style="width: 14%">Contact</th>
                            <th style="width: 14%">GST / Email</th>
                            <th style="width: 20%">Statutory</th>
                            <th style="width: 18%">Address</th>
                            <th style="width: 14%">Defaults</th>
                            <th style="width: 8%">Status</th>
                            <th style="width: 10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suppliers as $supplier)
                            @php($formId = 'supplier-form-'.$supplier->id)
                            <tr>
                                <td><input form="{{ $formId }}" name="name" value="{{ $supplier->name }}" required></td>
                                <td>
                                    <input form="{{ $formId }}" name="contact_person" value="{{ $supplier->contact_person }}" placeholder="Contact">
                                    <input form="{{ $formId }}" name="mobile" value="{{ $supplier->mobile }}" placeholder="Mobile">
                                </td>
                                <td>
                                    <input form="{{ $formId }}" name="gstin" value="{{ $supplier->gstin }}" placeholder="GSTIN">
                                    <input form="{{ $formId }}" name="email" type="email" value="{{ $supplier->email }}" placeholder="Email">
                                </td>
                                <td>
                                    <div class="supplier-statutory-stack">
                                        <select form="{{ $formId }}" name="gst_registration_type">
                                            @foreach (['' => 'GST type', 'regular' => 'Regular', 'composition' => 'Composition', 'unregistered' => 'Unregistered'] as $value => $label)
                                                <option value="{{ $value }}" @selected($supplier->gst_registration_type === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <select form="{{ $formId }}" name="gst_return_status">
                                            @foreach (['' => 'GST return', 'pending' => 'Pending', 'filed' => 'Filed', 'not_applicable' => 'Not Applicable'] as $value => $label)
                                                <option value="{{ $value }}" @selected($supplier->gst_return_status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input form="{{ $formId }}" name="tds_section" value="{{ $supplier->tds_section }}" placeholder="TDS Section">
                                        <input form="{{ $formId }}" name="tds_percent" type="number" min="0" max="100" step="0.01" value="{{ $supplier->tds_percent }}" placeholder="TDS %">
                                        <select form="{{ $formId }}" name="e_invoice_applicable">
                                            <option value="0" @selected(! $supplier->e_invoice_applicable)>E-Invoice: No</option>
                                            <option value="1" @selected($supplier->e_invoice_applicable)>E-Invoice: Yes</option>
                                        </select>
                                        <select form="{{ $formId }}" name="e_way_bill_applicable">
                                            <option value="0" @selected(! $supplier->e_way_bill_applicable)>E-Way Bill: No</option>
                                            <option value="1" @selected($supplier->e_way_bill_applicable)>E-Way Bill: Yes</option>
                                        </select>
                                        <select form="{{ $formId }}" name="vendor_reconciliation_status">
                                            @foreach (['' => 'Vendor recon', 'pending' => 'Pending', 'matched' => 'Matched', 'mismatch' => 'Mismatch', 'not_required' => 'Not Required'] as $value => $label)
                                                <option value="{{ $value }}" @selected($supplier->vendor_reconciliation_status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <textarea form="{{ $formId }}" name="auditor_export_note" placeholder="Auditor export note">{{ $supplier->auditor_export_note }}</textarea>
                                    </div>
                                </td>
                                <td><textarea form="{{ $formId }}" name="address">{{ $supplier->address }}</textarea></td>
                                <td>
                                    <input form="{{ $formId }}" name="default_dispatched_through" value="{{ $supplier->default_dispatched_through }}" placeholder="Dispatch">
                                    <input form="{{ $formId }}" name="default_destination" value="{{ $supplier->default_destination }}" placeholder="Destination">
                                    <textarea form="{{ $formId }}" name="default_terms" placeholder="Terms">{{ $supplier->default_terms }}</textarea>
                                </td>
                                <td>
                                    <select form="{{ $formId }}" name="is_active">
                                        <option value="1" @selected($supplier->is_active)>Active</option>
                                        <option value="0" @selected(! $supplier->is_active)>Inactive</option>
                                    </select>
                                </td>
                                <td>
                                    <div class="supplier-actions">
                                        <form id="{{ $formId }}" method="POST" action="{{ route('admin.suppliers.update', $supplier) }}">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                        <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete this supplier?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger small" type="submit">Delete</button>
                                        </form>
                                        <button class="btn small" form="{{ $formId }}" type="submit">Update</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8">No supplier added yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $suppliers->links('admin.pagination') }}</div>
        </section>
    </div>
@endsection
