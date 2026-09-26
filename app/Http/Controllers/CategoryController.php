<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::withCount('products', 'children')->with('parent');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('level')) {
            $request->level === 'root'
                ? $query->whereNull('parent_id')
                : $query->whereNotNull('parent_id');
        }

        $categories = $query->ordered()->paginate(20)->appends($request->query());

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $parents = Category::roots()->ordered()->get();

        return view('admin.categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $data = $this->validateCategory($request);

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Đã thêm danh mục mới!');
    }

    public function edit(Category $category)
    {
        // Không cho chọn chính nó làm cha (sẽ tạo vòng lặp cha - con)
        $parents = Category::roots()->ordered()->where('id', '!=', $category->id)->get();

        return view('admin.categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validateCategory($request, $category);

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Cập nhật danh mục thành công!');
    }

    public function destroy(Category $category)
    {
        // Chặn xóa danh mục còn sản phẩm để tránh mất dữ liệu ngoài ý muốn (FK là cascade)
        if ($category->products()->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Không thể xóa: Danh mục vẫn còn sản phẩm bên trong.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Đã xóa danh mục. Các danh mục con (nếu có) đã được chuyển thành danh mục gốc.');
    }

    private function validateCategory(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string|max:8',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'description' => 'nullable|string',
        ], [], [
            'name' => 'Tên danh mục',
            'parent_id' => 'Danh mục cha',
            'icon' => 'Biểu tượng',
            'sort_order' => 'Thứ tự hiển thị',
        ]);

        // Chặn tự trỏ vào chính mình và chặn chọn cha là một danh mục con
        // (hệ thống chỉ hỗ trợ cây 2 cấp cho đơn giản).
        if (! empty($data['parent_id'])) {
            if ($category && (int) $data['parent_id'] === (int) $category->id) {
                $data['parent_id'] = null;
            } elseif (Category::whereKey($data['parent_id'])->whereNotNull('parent_id')->exists()) {
                $data['parent_id'] = null;
            }
        }

        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
