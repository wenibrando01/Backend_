<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    
    public function index()
    {
        $posts = Post::latest()->get();
        return view('posts.index', compact('posts'));
    }

    
    public function create()
    {
        return view('posts.create');
    }

    
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $post = Post::create($data);

        return redirect()->route('posts.show', $post);
    }

   
    public function show(Post $post)
    {
        return view('posts.show', compact('post'));
    }

    
    public function edit(Post $post)
    {
        return view('posts.edit', compact('post'));
    }

   
    public function update(Request $request, Post $post)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $post->update($data);

        return redirect()->route('posts.show', $post);
    }

    
    public function destroy(Post $post)
    {
        $post->delete();
        return redirect()->route('posts.index');
    }
}
