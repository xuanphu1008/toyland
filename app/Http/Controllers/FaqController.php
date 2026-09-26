<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;

// Trang Câu hỏi thường gặp dành cho khách
class FaqController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim((string) $request->input('q'));

        $faqs = Faq::active()
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('question', 'like', '%' . $keyword . '%')
                      ->orWhere('answer', 'like', '%' . $keyword . '%');
                });
            })
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('category');

        return view('faq.index', compact('faqs', 'keyword'));
    }
}
