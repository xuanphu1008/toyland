<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

// Blog / tin tức về đồ chơi
class PostController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->input('q'));
        $category = $request->input('category');

        $posts = Post::published()
            ->when($keyword !== '', fn ($q) => $q->where('title', 'like', '%' . $keyword . '%'))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(9)
            ->appends($request->query());

        $categories = Post::published()->distinct()->orderBy('category')->pluck('category');

        return view('posts.index', compact('posts', 'categories', 'keyword', 'category'));
    }

    public function show(string $slug)
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();

        // Đếm lượt xem, dùng increment để không đụng tới updated_at
        $post->increment('views');

        $related = Post::published()
            ->where('id', '!=', $post->id)
            ->where('category', $post->category)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('posts.show', compact('post', 'related'));
    }
}
