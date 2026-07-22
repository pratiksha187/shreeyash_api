@extends('admin.layouts.app')

@section('title', 'Edit Project | Admin Panel')
@section('headerTitle', 'Edit Project')
@section('headerSubtitle', 'Update planning, status, and budget details')

@section('content')
    <div class="page-header">
        <div>
            <h1>Edit Project</h1>
            <p>{{ $project->name }}</p>
        </div>
        <a class="btn secondary" href="{{ route('admin.projects.show', $project) }}">Back to Project</a>
    </div>

    <form class="card form-card" method="POST" action="{{ route('admin.projects.update', $project) }}">
        @csrf
        @method('PUT')
        @include('admin.projects.form', ['project' => $project])

        <div class="actions">
            <button class="btn" type="submit">Update Project</button>
            <a class="btn secondary" href="{{ route('admin.projects.show', $project) }}">Cancel</a>
        </div>
    </form>
@endsection
