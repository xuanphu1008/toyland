<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

// Quản lý nội dung Câu hỏi thường gặp
class FaqController extends Controller
{
    public function index(Request $request)
    {
        $query = Faq::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', '%'.$search.'%')
                  ->orWhere('answer', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $faqs = $query->orderBy('category')->orderBy('sort_order')->orderBy('id')
            ->paginate(20)->appends($request->query());

        $categories = Faq::distinct()->orderBy('category')->pluck('category');

        return view('admin.faqs.index', compact('faqs', 'categories'));
    }

    public function create()
    {
        return view('admin.faqs.create');
    }

    public function store(Request $request)
    {
        Faq::create($this->validateFaq($request));

        return redirect()->route('admin.faqs.index')->with('success', 'Đã thêm câu hỏi thường gặp.');
    }

    public function edit(Faq $faq)
    {
        return view('admin.faqs.edit', compact('faq'));
    }

    public function update(Request $request, Faq $faq)
    {
        $faq->update($this->validateFaq($request));

        return redirect()->route('admin.faqs.index')->with('success', 'Đã cập nhật câu hỏi.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return redirect()->route('admin.faqs.index')->with('success', 'Đã xóa câu hỏi.');
    }

    private function validateFaq(Request $request): array
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [], [
            'category' => 'Nhóm câu hỏi',
            'question' => 'Câu hỏi',
            'answer' => 'Câu trả lời',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
