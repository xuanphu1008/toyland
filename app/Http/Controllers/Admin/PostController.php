<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// CMS - quản lý bài viết / tin tức của blog
class PostController extends Controller
{
    protected ImageUploadService $uploader;

    public function __construct(ImageUploadService $uploader)
    {
        $this->uploader = $uploader;
    }

    public function index(Request $request)
    {
        $query = Post::with('author');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . trim($request->search) . '%');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $posts = $query->orderByDesc('id')->paginate(15)->appends($request->query());
        $categories = Post::distinct()->orderBy('category')->pluck('category');

        return view('admin.posts.index', compact('posts', 'categories'));
    }

    public function create()
    {
        return view('admin.posts.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatePost($request);

        $data['slug'] = Post::uniqueSlug($data['title']);
        $data['author_id'] = Auth::id();

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploader->store($request->file('image'), 'posts');
        }

        Post::create($data);

        return redirect()->route('admin.posts.index')->with('success', 'Đã đăng bài viết.');
    }

    public function edit(Post $post)
    {
        return view('admin.posts.edit', compact('post'));
    }

    public function update(Request $request, Post $post)
    {
        $data = $this->validatePost($request);

        $data['slug'] = Post::uniqueSlug($data['title'], $post->id);

        if ($request->hasFile('image')) {
            // Tải ảnh mới lên trước, thành công mới xóa ảnh cũ
            $data['image'] = $this->uploader->store($request->file('image'), 'posts');
            $this->uploader->delete($post->image);
        }

        $post->update($data);

        return redirect()->route('admin.posts.index')->with('success', 'Đã cập nhật bài viết.');
    }

    public function destroy(Post $post)
    {
        $this->uploader->delete($post->image);

        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Đã xóa bài viết.');
    }

    private function validatePost(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'image' => ImageUploadService::validationRules(),
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ], ImageUploadService::validationMessages(), [
            'title' => 'Tiêu đề',
            'category' => 'Chuyên mục',
            'content' => 'Nội dung',
        ]);

        $data['is_published'] = $request->boolean('is_published');
        $data['published_at'] = $data['published_at'] ?? now();

        // image được xử lý riêng ở store/update nên bỏ khỏi mảng dữ liệu tại đây
        unset($data['image']);

        return $data;
    }
}
