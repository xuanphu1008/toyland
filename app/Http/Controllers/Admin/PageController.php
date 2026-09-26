<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

// CMS - quản lý các trang tĩnh (Về chúng tôi, Liên hệ, Chính sách...)
class PageController extends Controller
{
    public function index()
    {
        $pages = Page::orderBy('sort_order')->orderBy('id')->paginate(20);

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatePage($request);

        Page::create($data);

        return redirect()->route('admin.pages.index')->with('success', 'Đã tạo trang "' . $data['title'] . '".');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $page->update($this->validatePage($request, $page->id));

        return redirect()->route('admin.pages.index')->with('success', 'Đã cập nhật trang.');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Đã xóa trang.');
    }

    private function validatePage(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($ignoreId)],
            'content' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
            'show_in_footer' => ['nullable', 'boolean'],
        ], [], [
            'title' => 'Tiêu đề',
            'content' => 'Nội dung',
        ]);

        // Bỏ trống slug thì tự sinh từ tiêu đề (Str::slug xử lý được tiếng Việt có dấu)
        $data['slug'] = Str::slug($data['slug'] ?: $data['title']) ?: 'trang-' . time();
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_published'] = $request->boolean('is_published');
        $data['show_in_footer'] = $request->boolean('show_in_footer');

        return $data;
    }
}
