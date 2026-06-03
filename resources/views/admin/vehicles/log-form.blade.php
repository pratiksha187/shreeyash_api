@php
    $entryDate = old('entry_date', $vehicleLog?->entry_date?->format('Y-m-d') ?? now()->toDateString());
    $inAt = old('in_at', $vehicleLog?->in_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'));
    $outAt = old('out_at', $vehicleLog?->out_at?->format('Y-m-d\TH:i'));
@endphp

<section class="form-section">
    <h2 class="section-title">{{ $vehicleLog ? 'Edit Day Entry' : 'Day Entry' }}</h2>
    @if ($vehicleLog)
        <p>Selected entry #{{ $vehicleLog->id }} for {{ $vehicleLog->entry_date?->format('d M Y') }}.</p>
    @endif

    <div class="form-grid">
        <div class="field">
            <label for="entry_date">Entry Date</label>
            <input id="entry_date" name="entry_date" type="date" value="{{ $entryDate }}" required>
            @error('entry_date')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="driver_name">Driver Name</label>
            <input id="driver_name" name="driver_name" type="text" value="{{ old('driver_name', $vehicleLog?->driver_name ?? $vehicle->driver_name) }}">
            @error('driver_name')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="driver_mobile">Driver Mobile</label>
            <input id="driver_mobile" name="driver_mobile" type="text" value="{{ old('driver_mobile', $vehicleLog?->driver_mobile ?? $vehicle->driver_mobile) }}">
            @error('driver_mobile')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="challan_no">Challan No</label>
            <input id="challan_no" name="challan_no" type="text" value="{{ old('challan_no', $vehicleLog?->challan_no) }}">
            @error('challan_no')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="site_name">Site</label>
            <input id="site_name" name="site_name" type="text" value="{{ old('site_name', $vehicleLog?->site_name ?? $vehicle->default_site) }}">
            @error('site_name')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="diesel_added">Diesel Added</label>
            <input id="diesel_added" name="diesel_added" type="number" min="0" step="0.01" value="{{ old('diesel_added', $vehicleLog?->diesel_added ?? 0) }}">
            @error('diesel_added')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="start_reading">Start Reading</label>
            <input id="start_reading" name="start_reading" type="number" min="0" step="0.01" value="{{ old('start_reading', $vehicleLog?->start_reading ?? 0) }}">
            @error('start_reading')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="end_reading">End Reading</label>
            <input id="end_reading" name="end_reading" type="number" min="0" step="0.01" value="{{ old('end_reading', $vehicleLog?->end_reading ?? 0) }}">
            @error('end_reading')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="in_at">In Time</label>
            <input id="in_at" name="in_at" type="datetime-local" value="{{ $inAt }}" required>
            @error('in_at')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="out_at">Out / Logout Time</label>
            <input id="out_at" name="out_at" type="datetime-local" value="{{ $outAt }}">
            @error('out_at')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field full">
            <label for="purpose">Purpose</label>
            <textarea id="purpose" name="purpose" rows="3" placeholder="Delivery, visitor, service, or other reason">{{ old('purpose', $vehicleLog?->purpose) }}</textarea>
            @error('purpose')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field full">
            <label for="log_remarks">Remarks</label>
            <textarea id="log_remarks" name="remarks" rows="3">{{ old('remarks', $vehicleLog?->remarks) }}</textarea>
            @error('remarks')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>
    </div>
</section>
