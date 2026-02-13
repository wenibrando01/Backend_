@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>{{ $post->title ?? "Post #{$post->id}" }}</h1>

        <div>
            {!! nl2br(e($post->content)) !!}
        </div>

        <p class="text-muted">Created {{ $post->created_at->toDayDateTimeString() }}</p>

        <a href="{{ route('posts.index') }}" class="btn btn-secondary">Back</a>
        <a href="{{ route('posts.edit', $post) }}" class="btn btn-primary">Edit</a>
    </div>
@endsection
