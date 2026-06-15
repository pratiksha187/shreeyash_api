@extends('admin.layouts.app')

@section('title', 'Register New Employee | Admin Panel')
@section('headerTitle', 'Register New Employee')
@section('headerSubtitle', 'Create employee login, basic, and work details')

@section('content')
    <div class="page-header">
        <div>
            <h1>Register New Employee</h1>
            <p>Enter employee details. The mobile number and password are used for app login.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.employees.index') }}">Back to List</a>
    </div>

    <form class="card form-card" method="POST" action="{{ route('admin.employees.store') }}" target="_blank">
        @csrf

        <section class="form-section">
            <h2 class="section-title">Login Info</h2>

            <div class="form-grid">
                <div class="field">
                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                    @error('email')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                    @error('password')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required>
                </div>
            </div>
        </section>

        <section class="form-section">
            <h2 class="section-title">Basic Details</h2>

            <div class="form-grid">
                <div class="field">
                    <label for="mobile">Mobile</label>
                    <input id="mobile" name="mobile" type="text" value="{{ old('mobile') }}" required>
                    @error('mobile')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field"></div>

                <div class="field">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">Select</option>
                        <option value="male" @selected(old('gender') === 'male')>Male</option>
                        <option value="female" @selected(old('gender') === 'female')>Female</option>
                        <option value="other" @selected(old('gender') === 'other')>Other</option>
                    </select>
                    @error('gender')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="marital_status">Marital Status</label>
                    <select id="marital_status" name="marital_status">
                        <option value="">Select</option>
                        <option value="single" @selected(old('marital_status') === 'single')>Single</option>
                        <option value="married" @selected(old('marital_status') === 'married')>Married</option>
                    </select>
                    @error('marital_status')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-grid three">
                <div class="field">
                    <label for="date_of_birth">Date of Birth</label>
                    <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}">
                    @error('date_of_birth')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="join_date">Join Date</label>
                    <input id="join_date" name="join_date" type="date" value="{{ old('join_date') }}">
                    @error('join_date')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="confirmation_date">Confirmation Date</label>
                    <input id="confirmation_date" name="confirmation_date" type="date" value="{{ old('confirmation_date') }}">
                    @error('confirmation_date')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <label for="probation_months">Probation Months</label>
                    <input id="probation_months" name="probation_months" type="number" min="0" value="{{ old('probation_months') }}">
                    @error('probation_months')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="aadhaar_number">Aadhaar Number</label>
                    <input id="aadhaar_number" name="aadhaar_number" type="text" value="{{ old('aadhaar_number') }}">
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
                    <input id="hours_per_day" name="hours_per_day" type="number" min="0" max="24" step="0.5" value="{{ old('hours_per_day') }}">
                    @error('hours_per_day')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="days_per_week">Days per Week</label>
                    <input id="days_per_week" name="days_per_week" type="number" min="0" max="7" value="{{ old('days_per_week') }}">
                    @error('days_per_week')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="salary">Salary</label>
                    <input id="salary" name="salary" type="number" min="0" step="0.01" value="{{ old('salary') }}">
                    @error('salary')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="insurance">Insurance</label>
                    <input id="insurance" name="insurance" type="number" min="0" step="0.01" value="{{ old('insurance') }}">
                    @error('insurance')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="pt">PT</label>
                    <input id="pt" name="pt" type="number" min="0" step="0.01" value="{{ old('pt') }}">
                    @error('pt')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="advance">Advance</label>
                    <input id="advance" name="advance" type="number" min="0" step="0.01" value="{{ old('advance') }}">
                    @error('advance')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="pf">PF</label>
                    <input id="pf" name="pf" type="number" min="0" step="0.01" value="{{ old('pf') }}">
                    @error('pf')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="designation">Designation</label>
                    <input id="designation" name="designation" type="text" value="{{ old('designation') }}" placeholder="Employee designation">
                    @error('designation')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>

        <div class="actions">
            <button class="btn" type="submit">Register Employee</button>
            <a class="btn secondary" href="{{ route('admin.employees.index') }}">Cancel</a>
        </div>
    </form>
@endsection
