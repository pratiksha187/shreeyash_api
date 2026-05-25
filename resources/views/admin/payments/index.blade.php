@extends('admin.layouts.app')

@section('title', 'Payments | Admin Panel')
@section('headerTitle', 'Payments')
@section('headerSubtitle', 'Generate salary payment and PDF slips')

@section('content')
    <div class="page-header">
        <div>
            <h1>Payments</h1>
            <p>Select an employee and date range to calculate payable salary.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">
            {{ session('success') }}
            @if (session('payment_id'))
                <a class="table-link" href="{{ route('admin.payments.slip', session('payment_id')) }}" target="_blank">Open PDF Slip</a>
            @endif
        </div>
    @endif

    @if (session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    <form class="card form-card report-filter" method="POST" action="{{ route('admin.payments.generate') }}">
        @csrf
        <section class="form-section">
            <h2 class="section-title">Generate Payment</h2>
            <div class="form-grid three">
                <div class="field">
                    <label for="user_id">Employee</label>
                    <select id="user_id" name="user_id" required>
                        <option value="">Select Employee</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(old('user_id') == $employee->id)>
                                {{ $employee->name }}{{ $employee->mobile ? ' - ' . $employee->mobile : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="from_date">From Date</label>
                    <input id="from_date" name="from_date" type="date" value="{{ old('from_date', $defaultFromDate) }}" required>
                    @error('from_date')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="to_date">To Date</label>
                    <input id="to_date" name="to_date" type="date" value="{{ old('to_date', $defaultToDate) }}" required>
                    @error('to_date')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <button class="btn" type="submit">Calculate Payment</button>
                </div>
            </div>
        </section>
    </form>

    <div class="page-header">
        <div>
            <h1>Generated Payments</h1>
            <p>Saved salary calculations and PDF slips.</p>
        </div>
    </div>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Period</th>
                    <th>Paid Days</th>
                    <th>Gross Payable</th>
                    <th>Deductions</th>
                    <th>Net Payable</th>
                    <th>PDF</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td>{{ $payment->user?->name ?? '-' }}</td>
                        <td>{{ $payment->from_date?->format('d M Y') }} - {{ $payment->to_date?->format('d M Y') }}</td>
                        <td>{{ $payment->present_days }}</td>
                        <td>{{ number_format((float) $payment->gross_payable, 2) }}</td>
                        <td>{{ number_format((float) $payment->total_deduction, 2) }}</td>
                        <td>{{ number_format((float) $payment->net_payable, 2) }}</td>
                        <td>
                            <a class="table-link" href="{{ route('admin.payments.slip', $payment) }}" target="_blank">Open Slip</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="7">No payments generated yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $payments->links() }}
    </div>
@endsection
