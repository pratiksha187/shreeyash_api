@extends('admin.layouts.app')

@section('title', 'Edit Vehicle Entry | Admin Panel')
@section('headerTitle', 'Edit Vehicle Entry')
@section('headerSubtitle', 'Update one day-wise vehicle record')

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $vehicle->vehicle_number }}</h1>
            <p>Edit entry for {{ $vehicleLog->entry_date?->format('d M Y') }}.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.vehicles.show', ['vehicle' => $vehicle, 'month' => $vehicleLog->entry_date?->format('Y-m')]) }}">Back to Calendar</a>
    </div>

    <form class="card form-card" method="POST" action="{{ route('admin.vehicles.logs.update', [$vehicle, $vehicleLog]) }}">
        @csrf
        @method('PUT')

        @include('admin.vehicles.log-form', ['vehicle' => $vehicle, 'vehicleLog' => $vehicleLog])

        <div class="actions">
            <button class="btn" type="submit">Update Entry</button>
            <a class="btn secondary" href="{{ route('admin.vehicles.show', ['vehicle' => $vehicle, 'month' => $vehicleLog->entry_date?->format('Y-m')]) }}">Cancel</a>
        </div>
    </form>
@endsection
