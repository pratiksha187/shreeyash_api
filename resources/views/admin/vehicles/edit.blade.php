@extends('admin.layouts.app')

@section('title', 'Edit Vehicle | Admin Panel')
@section('headerTitle', 'Edit Vehicle')
@section('headerSubtitle', 'Update vehicle master details')

@section('content')
    <div class="page-header">
        <div>
            <h1>Edit Vehicle</h1>
            <p>Update vehicle number, type, owner, and default driver details.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.vehicles.show', $vehicle) }}">Back to Calendar</a>
    </div>

    <form class="card form-card" method="POST" action="{{ route('admin.vehicles.update', $vehicle) }}">
        @csrf
        @method('PUT')

        @include('admin.vehicles.form', ['vehicle' => $vehicle])

        <div class="actions">
            <button class="btn" type="submit">Update Vehicle</button>
            <a class="btn secondary" href="{{ route('admin.vehicles.show', $vehicle) }}">Cancel</a>
        </div>
    </form>
@endsection
