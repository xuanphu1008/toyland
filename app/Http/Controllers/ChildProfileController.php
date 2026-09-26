<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ChildProfile;
use App\Models\Product;
use App\Services\GiftFinderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * HỒ SƠ CỦA BÉ + SỔ SINH NHẬT
 *
 * BẢO MẬT: mọi thao tác đều đi qua $this->owned() để lấy hồ sơ, hàm đó luôn
 * lọc theo user_id của người đang đăng nhập. Nhờ vậy không có đường nào để
 * khách A sửa hay xem hồ sơ con của khách B, kể cả khi họ đoán đúng id trên URL.
 */
class ChildProfileController extends Controller
{
    /** Tối đa bao nhiêu bé một tài khoản - chặn spam chứ không hạn chế thật */
    private const MAX_CHILDREN = 10;

    public function __construct(private GiftFinderService $giftFinder)
    {
    }

    public function index()
    {
        $children = $this->children();

        // Gợi ý riêng cho từng bé theo tuổi HIỆN TẠI
        $suggestions = $children->mapWithKeys(fn (ChildProfile $child) => [
            $child->id => $this->giftFinder->forChild($child, 4),
        ]);

        return view('children.index', compact('children', 'suggestions'));
    }

    /**
     * SỔ SINH NHẬT - danh sách bé xếp theo sinh nhật gần nhất.
     *
     * Điểm khác biệt so với trang hồ sơ: quà được gợi ý theo tuổi bé SẮP TRÒN
     * chứ không phải tuổi hiện tại. Bé sắp lên 6 mà tặng đồ của tuổi 5 thì
     * món quà lỗi thời ngay trong tháng đầu.
     */
    public function birthdays()
    {
        $children = $this->children()->sortBy('days_until_birthday')->values();

        $suggestions = $children->mapWithKeys(fn (ChildProfile $child) => [
            $child->id => $this->giftFinder->forChild($child, 4, $child->gift_age_months),
        ]);

        return view('children.birthdays', compact('children', 'suggestions'));
    }

    public function create()
    {
        if ($this->children()->count() >= self::MAX_CHILDREN) {
            return redirect()->route('children.index')
                ->with('error', 'Mỗi tài khoản chỉ lưu được tối đa ' . self::MAX_CHILDREN . ' hồ sơ bé.');
        }

        return view('children.form', [
            'child' => new ChildProfile(['avatar' => '🧒']),
        ] + $this->formData());
    }

    public function store(Request $request)
    {
        if ($this->children()->count() >= self::MAX_CHILDREN) {
            return redirect()->route('children.index')
                ->with('error', 'Mỗi tài khoản chỉ lưu được tối đa ' . self::MAX_CHILDREN . ' hồ sơ bé.');
        }

        $data = $this->validated($request);
        $data['user_id'] = Auth::id();

        $child = ChildProfile::create($data);

        // Khai bé đầu tiên thì chọn luôn bé đó để cá nhân hóa trang chủ
        if ($this->children()->count() === 1) {
            session(['active_child_id' => $child->id]);
        }

        return redirect()->route('children.index')
            ->with('success', 'Đã thêm hồ sơ của ' . $child->name . '.');
    }

    public function edit(int $id)
    {
        return view('children.form', [
            'child' => $this->owned($id),
        ] + $this->formData());
    }

    public function update(Request $request, int $id)
    {
        $child = $this->owned($id);
        $child->update($this->validated($request));

        return redirect()->route('children.index')
            ->with('success', 'Đã cập nhật hồ sơ của ' . $child->name . '.');
    }

    public function destroy(int $id)
    {
        $child = $this->owned($id);
        $name = $child->name;
        $child->delete();

        // Xóa bé đang được chọn thì bỏ luôn lựa chọn, tránh trỏ vào id không còn
        if ((int) session('active_child_id') === (int) $id) {
            session()->forget('active_child_id');
        }

        return redirect()->route('children.index')->with('success', 'Đã xóa hồ sơ của ' . $name . '.');
    }

    /**
     * Chọn bé đang xem. Lưu ở SESSION chứ không lưu vào CSDL vì đây là lựa chọn
     * tạm thời của phiên duyệt web - bố mẹ đổi qua đổi lại giữa các con liên tục.
     */
    public function select(Request $request, int $id)
    {
        $child = $this->owned($id);
        session(['active_child_id' => $child->id]);

        return back()->with('success', 'Đang xem đồ chơi phù hợp với ' . $child->name . '.');
    }

    public function clearSelection()
    {
        session()->forget('active_child_id');

        return back();
    }

    // ===================================================================

    private function children()
    {
        return ChildProfile::where('user_id', Auth::id())
            ->with('favoriteCategory')
            ->orderBy('birthday')
            ->get();
    }

    /** Lấy hồ sơ CỦA CHÍNH người đang đăng nhập, không có thì 404 */
    private function owned(int $id): ChildProfile
    {
        return ChildProfile::where('user_id', Auth::id())->findOrFail($id);
    }

    /** Dữ liệu đổ vào form: danh sách nhân vật và danh mục lấy từ hàng thật */
    private function formData(): array
    {
        return [
            'characters' => Product::whereNotNull('character_name')
                ->where('character_name', '!=', '')
                ->distinct()->orderBy('character_name')->pluck('character_name'),
            'categories' => Category::roots()->ordered()->get(),
            'avatars' => ChildProfile::AVATARS,
            'genders' => ChildProfile::GENDERS,
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            // Không cho ngày sinh ở tương lai, và không nhận bé "trên 18 tuổi"
            // vì đây là web đồ chơi trẻ em.
            'birthday' => ['required', 'date', 'before_or_equal:today', 'after:' . now()->subYears(18)->toDateString()],
            'gender' => ['nullable', 'in:' . implode(',', array_keys(ChildProfile::GENDERS))],
            'avatar' => ['nullable', 'string', 'max:8'],
            'interests' => ['nullable', 'array', 'max:8'],
            'interests.*' => ['string', 'max:120'],
            'favorite_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], [
            'name' => 'Tên bé',
            'birthday' => 'Ngày sinh',
            'gender' => 'Giới tính',
            'interests' => 'Nhân vật yêu thích',
            'favorite_category_id' => 'Nhóm đồ chơi yêu thích',
            'note' => 'Ghi chú',
        ]);

        $data['interests'] = ChildProfile::packInterests($data['interests'] ?? null);

        return $data;
    }
}
