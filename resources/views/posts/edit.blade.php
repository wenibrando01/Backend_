@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Edit Post</h1>

        <form action="{{ route('posts.update', $post) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input id="title" name="title" class="form-control" value="{{ old('title', $post->title) }}">
            </div>

            <div class="mb-3">
                <label for="content" class="form-label">Content</label>
                <textarea id="content" name="content" class="form-control" rows="6">{{ old('content', $post->content) }}</textarea>
            </div>

            <button class="btn btn-primary">Update</button>
            <a href="{{ route('posts.show', $post) }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
@endsection
