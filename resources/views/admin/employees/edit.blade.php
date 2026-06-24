@extends('admin.layouts.app')

@section('title', 'Edit Employee | Admin Panel')
@section('headerTitle', 'Edit Employee')
@section('headerSubtitle', 'Update employee login, basic, and work details')

@section('content')
    <div class="page-header">
        <div>
            <h1>Edit Employee</h1>
            <p>Update {{ $employee->name }} details. Leave password blank to keep the current password.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.employees.index') }}">Back to List</a>
    </div>

    <form class="card form-card" method="POST" action="{{ route('admin.employees.update', $employee) }}">
        @csrf
        @method('PUT')

        @include('admin.employees._form', ['employee' => $employee])

        <div class="actions">
            <button class="btn" type="submit">Update Employee</button>
            <a class="btn secondary" href="{{ route('admin.employees.index') }}">Cancel</a>
        </div>
    </form>
@endsection
