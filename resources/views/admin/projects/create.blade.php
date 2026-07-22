@extends('admin.layouts.app')

@section('title', 'Add Project | Admin Panel')
@section('headerTitle', 'Add Project')
@section('headerSubtitle', 'Create a project for planning and task tracking')

@section('content')
    <div class="page-header">
        <div>
            <h1>Add Project</h1>
            <p>Planning manager can create the project first, then assign tasks to engineers and supervisors.</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.projects.index') }}">Back to Projects</a>
    </div>

    <form class="card form-card" method="POST" action="{{ route('admin.projects.store') }}">
        @csrf
        @include('admin.projects.form', ['project' => null])

        <div class="actions">
            <button class="btn" type="submit">Save Project</button>
            <a class="btn secondary" href="{{ route('admin.projects.index') }}">Cancel</a>
        </div>
    </form>
@endsection
