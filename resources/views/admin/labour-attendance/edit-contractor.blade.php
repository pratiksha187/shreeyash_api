@extends('admin.layouts.app')

@section('title', 'Edit Contractor | Admin Panel')
@section('headerTitle', 'Edit Contractor')
@section('headerSubtitle', 'Update contractor master details')
@section('bodyClass', 'contractor-page')

@section('content')
    <style>
        body.contractor-page {
            overflow-x: hidden;
        }

        body.contractor-page .main {
            max-width: 1280px;
        }

        body.contractor-page .page-header {
            align-items: flex-start;
        }

        body.contractor-page .page-header > div {
            min-width: 0;
        }

        body.contractor-page .page-header .btn {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .contractor-form {
            display: grid;
            gap: 28px;
            padding: 28px 32px;
            overflow: hidden;
        }

        .contractor-form .form-section {
            margin-bottom: 0;
        }

        .contractor-form .section-title {
            margin-bottom: 18px;
            font-size: 22px;
        }

        .contractor-form .form-grid {
            align-items: start;
            gap: 18px 24px;
        }

        .contractor-form .field {
            min-width: 0;
        }

        .contractor-form .actions {
            justify-content: flex-start;
            margin-top: 0;
        }

        .contractor-wide {
            grid-column: span 2;
        }

        .contractor-full {
            grid-column: 1 / -1;
        }

        @media (max-width: 760px) {
            .contractor-wide {
                grid-column: 1 / -1;
            }
        }
    </style>

    <div class="page-header">
        <div>
            <h1>Edit Contractor</h1>
            <p>Update contractor agreement, work order, progress and RA billing details.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.contractors.index') }}">Back to Contractor Master</a>
    </div>

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <form class="card form-card contractor-form" method="POST" action="{{ route('admin.contractors.update', $contractor) }}">
        @csrf
        @method('PUT')

        <section class="form-section">
            <h2 class="section-title">Contractor Details</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="name">Contractor Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $contractor->name) }}" required>
                </div>
                <div class="field">
                    <label for="mobile">Mobile</label>
                    <input id="mobile" name="mobile" type="text" value="{{ old('mobile', $contractor->mobile) }}">
                </div>
                <div class="field">
                    <label for="is_active">Status</label>
                    <select id="is_active" name="is_active">
                        <option value="1" @selected((string) old('is_active', (int) $contractor->is_active) === '1')>Active</option>
                        <option value="0" @selected((string) old('is_active', (int) $contractor->is_active) === '0')>Inactive</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h2 class="section-title">Contract & Work Order</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="agreement_no">Agreement No</label>
                    <input id="agreement_no" name="agreement_no" type="text" value="{{ old('agreement_no', $contractor->agreement_no) }}">
                </div>
                <div class="field">
                    <label for="contract_no">Contract No</label>
                    <input id="contract_no" name="contract_no" type="text" value="{{ old('contract_no', $contractor->contract_no) }}">
                </div>
                <div class="field">
                    <label for="work_order_no">Work Order No</label>
                    <input id="work_order_no" name="work_order_no" type="text" value="{{ old('work_order_no', $contractor->work_order_no) }}">
                </div>
                <div class="field">
                    <label for="contract_start_date">Start Date</label>
                    <input id="contract_start_date" name="contract_start_date" type="date" value="{{ old('contract_start_date', $contractor->contract_start_date?->format('Y-m-d')) }}">
                </div>
                <div class="field">
                    <label for="contract_end_date">End Date</label>
                    <input id="contract_end_date" name="contract_end_date" type="date" value="{{ old('contract_end_date', $contractor->contract_end_date?->format('Y-m-d')) }}">
                </div>
                <div class="field">
                    <label for="contract_value">Contract Value</label>
                    <input id="contract_value" name="contract_value" type="number" min="0" step="0.01" value="{{ old('contract_value', $contractor->contract_value) }}">
                </div>
            </div>
        </section>

        <section class="form-section">
            <h2 class="section-title">Progress & Billing</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="progress_percent">Progress %</label>
                    <input id="progress_percent" name="progress_percent" type="number" min="0" max="100" step="0.01" value="{{ old('progress_percent', $contractor->progress_percent) }}">
                </div>
                <div class="field">
                    <label for="last_measurement_date">Measurement Date</label>
                    <input id="last_measurement_date" name="last_measurement_date" type="date" value="{{ old('last_measurement_date', $contractor->last_measurement_date?->format('Y-m-d')) }}">
                </div>
                <div class="field">
                    <label for="last_ra_bill_no">RA Bill No</label>
                    <input id="last_ra_bill_no" name="last_ra_bill_no" type="text" value="{{ old('last_ra_bill_no', $contractor->last_ra_bill_no) }}">
                </div>
                <div class="field">
                    <label for="last_ra_bill_amount">RA Bill Amount</label>
                    <input id="last_ra_bill_amount" name="last_ra_bill_amount" type="number" min="0" step="0.01" value="{{ old('last_ra_bill_amount', $contractor->last_ra_bill_amount) }}">
                </div>
                <div class="field">
                    <label for="retention_percent">Retention %</label>
                    <input id="retention_percent" name="retention_percent" type="number" min="0" max="100" step="0.01" value="{{ old('retention_percent', $contractor->retention_percent) }}">
                </div>
                <div class="field">
                    <label for="recovery_amount">Recovery Amount</label>
                    <input id="recovery_amount" name="recovery_amount" type="number" min="0" step="0.01" value="{{ old('recovery_amount', $contractor->recovery_amount) }}">
                </div>
                <div class="field">
                    <label for="tds_percent">TDS %</label>
                    <input id="tds_percent" name="tds_percent" type="number" min="0" max="100" step="0.01" value="{{ old('tds_percent', $contractor->tds_percent) }}">
                </div>
                <div class="field">
                    <label for="gst_percent">GST %</label>
                    <input id="gst_percent" name="gst_percent" type="number" min="0" max="100" step="0.01" value="{{ old('gst_percent', $contractor->gst_percent) }}">
                </div>
                <div class="field">
                    <label for="net_payable_amount">Net Payable</label>
                    <input id="net_payable_amount" name="net_payable_amount" type="number" min="0" step="0.01" value="{{ old('net_payable_amount', $contractor->net_payable_amount) }}">
                </div>
                <div class="field">
                    <label for="renewal_due_date">Renewal Due Date</label>
                    <input id="renewal_due_date" name="renewal_due_date" type="date" value="{{ old('renewal_due_date', $contractor->renewal_due_date?->format('Y-m-d')) }}">
                </div>
                <div class="field contractor-wide">
                    <label for="last_measurement_summary">Measurement Details</label>
                    <textarea id="last_measurement_summary" name="last_measurement_summary" rows="2">{{ old('last_measurement_summary', $contractor->last_measurement_summary) }}</textarea>
                </div>
                <div class="field contractor-full">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="2">{{ old('remarks', $contractor->remarks) }}</textarea>
                </div>
            </div>
        </section>

        <div class="actions">
            <button class="btn" type="submit">Update Contractor</button>
            <a class="btn secondary" href="{{ route('admin.contractors.index') }}">Cancel</a>
        </div>
    </form>
@endsection
