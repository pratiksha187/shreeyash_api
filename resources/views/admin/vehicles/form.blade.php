<section class="form-section">
    <h2 class="section-title">Vehicle Details</h2>

    <div class="form-grid">
        <div class="field">
            <label for="vehicle_number">Vehicle Number</label>
            <input id="vehicle_number" name="vehicle_number" type="text" value="{{ old('vehicle_number', $vehicle?->vehicle_number) }}" placeholder="MH 12 AB 1234" required>
            @error('vehicle_number')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="vehicle_type">Vehicle Type</label>
            <input id="vehicle_type" name="vehicle_type" type="text" value="{{ old('vehicle_type', $vehicle?->vehicle_type) }}" placeholder="Truck, Car, Bike">
            @error('vehicle_type')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="owner_name">Owner Name</label>
            <input id="owner_name" name="owner_name" type="text" value="{{ old('owner_name', $vehicle?->owner_name) }}">
            @error('owner_name')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="driver_name">Default Driver Name</label>
            <input id="driver_name" name="driver_name" type="text" value="{{ old('driver_name', $vehicle?->driver_name) }}">
            @error('driver_name')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="driver_mobile">Default Driver Mobile</label>
            <input id="driver_mobile" name="driver_mobile" type="text" value="{{ old('driver_mobile', $vehicle?->driver_mobile) }}">
            @error('driver_mobile')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="default_site">Default Site</label>
            <input id="default_site" name="default_site" type="text" value="{{ old('default_site', $vehicle?->default_site) }}" placeholder="Cluster 4">
            @error('default_site')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="billing_cycle_start_day">Billing Cycle Start Day</label>
            <input id="billing_cycle_start_day" name="billing_cycle_start_day" type="number" min="1" max="31" step="1" value="{{ old('billing_cycle_start_day', $vehicle?->billing_cycle_start_day ?? 1) }}">
            @error('billing_cycle_start_day')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>
    </div>
</section>

<section class="form-section">
    <h2 class="section-title">Billing Details</h2>

    <div class="form-grid three">
        <div class="field">
            <label for="fixed_monthly_amount">Fixed Monthly Amount</label>
            <input id="fixed_monthly_amount" name="fixed_monthly_amount" type="number" min="0" step="0.01" value="{{ old('fixed_monthly_amount', $vehicle?->fixed_monthly_amount ?? 0) }}">
            @error('fixed_monthly_amount')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="ot_rate">OT Rate</label>
            <input id="ot_rate" name="ot_rate" type="number" min="0" step="0.01" value="{{ old('ot_rate', $vehicle?->ot_rate ?? 0) }}">
            @error('ot_rate')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="hire_per_day_rate">Per Day Rate</label>
            <input id="hire_per_day_rate" name="hire_per_day_rate" type="number" min="0" step="0.01" value="{{ old('hire_per_day_rate', $vehicle?->hire_per_day_rate ?? 0) }}">
            @error('hire_per_day_rate')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="hire_per_hour_rate">Per Hour Rate</label>
            <input id="hire_per_hour_rate" name="hire_per_hour_rate" type="number" min="0" step="0.01" value="{{ old('hire_per_hour_rate', $vehicle?->hire_per_hour_rate ?? 0) }}">
            @error('hire_per_hour_rate')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="tds_percentage">TDS Percentage</label>
            <input id="tds_percentage" name="tds_percentage" type="number" min="0" max="100" step="0.01" value="{{ old('tds_percentage', $vehicle?->tds_percentage ?? 1) }}">
            @error('tds_percentage')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="gst_percentage">GST Percentage</label>
            <input id="gst_percentage" name="gst_percentage" type="number" min="0" max="100" step="0.01" value="{{ old('gst_percentage', $vehicle?->gst_percentage ?? 18) }}">
            @error('gst_percentage')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="extra_sunday_paid_amount">Extra Sunday Paid</label>
            <input id="extra_sunday_paid_amount" name="extra_sunday_paid_amount" type="number" min="0" step="0.01" value="{{ old('extra_sunday_paid_amount', $vehicle?->extra_sunday_paid_amount ?? 0) }}">
            @error('extra_sunday_paid_amount')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field full">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" name="remarks" rows="3">{{ old('remarks', $vehicle?->remarks) }}</textarea>
            @error('remarks')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>
    </div>
</section>
