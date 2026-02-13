@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Posts</h1>
        <a href="{{ route('posts.create') }}" class="btn btn-primary">Create Post</a>

        @if($posts->isEmpty())
            <p>No posts yet.</p>
        @else
            <ul>
                @foreach($posts as $post)
                    <li>
                        <a href="{{ route('posts.show', $post) }}">{{ $post->title ?? "Post #{$post->id}" }}</a>
                        <small class="text-muted">{{ $post->created_at->diffForHumans() }}</small>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
