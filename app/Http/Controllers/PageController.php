<?php

namespace App\Http\Controllers;

use App\Models\Page;

// Hiển thị trang tĩnh do admin soạn (Về chúng tôi, Chính sách đổi trả...)
class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        return view('pages.show', compact('page'));
    }
}
