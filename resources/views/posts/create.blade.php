@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Create Post</h1>

        <form action="{{ route('posts.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input id="title" name="title" class="form-control" value="{{ old('title') }}">
            </div>

            <div class="mb-3">
                <label for="content" class="form-label">Content</label>
                <textarea id="content" name="content" class="form-control" rows="6">{{ old('content') }}</textarea>
            </div>

            <button class="btn btn-primary">Save</button>
            <a href="{{ route('posts.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
@endsection
