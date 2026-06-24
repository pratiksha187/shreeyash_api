@php
    $employee = $employee ?? null;
    $isEditing = (bool) $employee;
@endphp

<section class="form-section">
    <h2 class="section-title">Login Info</h2>

    <div class="form-grid">
        <div class="field">
            <label for="name">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name', $employee->name ?? '') }}" required>
            @error('name')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $employee->email ?? '') }}" required>
            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="password">Password</label>
            <div class="password-eye-wrap">
                <input id="password" name="password" type="password" @unless($isEditing) required @endunless>
                <button class="password-eye-button" type="button" data-password-toggle aria-controls="password" aria-label="Show password">
                    <span class="password-eye-icon" aria-hidden="true"></span>
                </button>
            </div>
            @if ($isEditing)
                <div class="help-text">Leave blank to keep current password.</div>
            @endif
            @error('password')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm Password</label>
            <div class="password-eye-wrap">
                <input id="password_confirmation" name="password_confirmation" type="password" @unless($isEditing) required @endunless>
                <button class="password-eye-button" type="button" data-password-toggle aria-controls="password_confirmation" aria-label="Show confirm password">
                    <span class="password-eye-icon" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</section>

<section class="form-section">
    <h2 class="section-title">Basic Details</h2>

    <div class="form-grid">
        <div class="field">
            <label for="mobile">Mobile</label>
            <input id="mobile" name="mobile" type="text" value="{{ old('mobile', $employee->mobile ?? '') }}" required>
            @error('mobile')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field"></div>

        <div class="field">
            <label for="gender">Gender</label>
            <select id="gender" name="gender">
                @php($gender = old('gender', $employee->gender ?? ''))
                <option value="">Select</option>
                <option value="male" @selected($gender === 'male')>Male</option>
                <option value="female" @selected($gender === 'female')>Female</option>
                <option value="other" @selected($gender === 'other')>Other</option>
            </select>
            @error('gender')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="marital_status">Marital Status</label>
            <select id="marital_status" name="marital_status">
                @php($maritalStatus = old('marital_status', $employee->marital_status ?? ''))
                <option value="">Select</option>
                <option value="single" @selected($maritalStatus === 'single')>Single</option>
                <option value="married" @selected($maritalStatus === 'married')>Married</option>
            </select>
            @error('marital_status')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="form-grid three">
        <div class="field">
            <label for="date_of_birth">Date of Birth</label>
            <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth', $employee?->date_of_birth?->format('Y-m-d')) }}">
            @error('date_of_birth')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="join_date">Join Date</label>
            <input id="join_date" name="join_date" type="date" value="{{ old('join_date', $employee?->join_date?->format('Y-m-d')) }}">
            @error('join_date')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="confirmation_date">Confirmation Date</label>
            <input id="confirmation_date" name="confirmation_date" type="date" value="{{ old('confirmation_date', $employee?->confirmation_date?->format('Y-m-d')) }}">
            @error('confirmation_date')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="form-grid">
        <div class="field">
            <label for="probation_months">Probation Months</label>
            <input id="probation_months" name="probation_months" type="number" min="0" value="{{ old('probation_months', $employee->probation_months ?? '') }}">
            @error('probation_months')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="aadhaar_number">Aadhaar Number</label>
            <input id="aadhaar_number" name="aadhaar_number" type="text" value="{{ old('aadhaar_number', $employee->aadhaar_number ?? '') }}">
            @error('aadhaar_number')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>
    </div>
</section>

<section class="form-section">
    <h2 class="section-title">Work Details</h2>

    <div class="form-grid three">
        <div class="field">
            <label for="hours_per_day">Hours per Day</label>
            <input id="hours_per_day" name="hours_per_day" type="number" min="0" max="24" step="0.5" value="{{ old('hours_per_day', $employee->hours_per_day ?? '') }}">
            @error('hours_per_day')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="days_per_week">Days per Week</label>
            <input id="days_per_week" name="days_per_week" type="number" min="0" max="7" value="{{ old('days_per_week', $employee->days_per_week ?? '') }}">
            @error('days_per_week')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="salary">Salary</label>
            <input id="salary" name="salary" type="number" min="0" step="0.01" value="{{ old('salary', $employee->salary ?? '') }}">
            @error('salary')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="insurance">Insurance</label>
            <input id="insurance" name="insurance" type="number" min="0" step="0.01" value="{{ old('insurance', $employee->insurance ?? '') }}">
            @error('insurance')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="pt">PT</label>
            <input id="pt" name="pt" type="number" min="0" step="0.01" value="{{ old('pt', $employee->pt ?? '') }}">
            @error('pt')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="advance">Advance</label>
            <input id="advance" name="advance" type="number" min="0" step="0.01" value="{{ old('advance', $employee->advance ?? '') }}">
            @error('advance')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="pf">PF</label>
            <input id="pf" name="pf" type="number" min="0" step="0.01" value="{{ old('pf', $employee->pf ?? '') }}">
            @error('pf')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="designation">Designation</label>
            <select id="designation" name="designation">
                @php($designation = old('designation', $employee->designation ?? ''))
                <option value="">Select designation</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected($designation === $role)>{{ $role }}</option>
                @endforeach
            </select>
            @error('designation')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>
    </div>
</section>
