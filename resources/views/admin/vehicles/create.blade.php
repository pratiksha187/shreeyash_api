@extends('admin.layouts.app')

@section('title', 'Add Vehicle | Admin Panel')
@section('headerTitle', 'Add Vehicle')
@section('headerSubtitle', 'Create a vehicle master record')

@section('content')
    <div class="page-header">
        <div>
            <h1>Add Vehicle</h1>
            <p>Save vehicle details once, then add day-wise in and out entries from its calendar.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.vehicles.index') }}">Back to Vehicles</a>
    </div>

    <form class="card form-card" method="POST" action="{{ route('admin.vehicles.store') }}">
        @csrf

        @include('admin.vehicles.form', ['vehicle' => null])

        <div class="actions">
            <button class="btn" type="submit">Save Vehicle</button>
            <a class="btn secondary" href="{{ route('admin.vehicles.index') }}">Cancel</a>
        </div>
    </form>
@endsection
