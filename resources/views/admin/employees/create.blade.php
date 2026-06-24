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

    <form class="card form-card" method="POST" action="{{ route('admin.employees.store') }}">
        @csrf

        @include('admin.employees._form')

        <div class="actions">
            <button class="btn" type="submit">Register Employee</button>
            <a class="btn secondary" href="{{ route('admin.employees.index') }}">Cancel</a>
        </div>
    </form>
@endsection
