<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    protected ImageUploadService $uploader;

    public function __construct(ImageUploadService $uploader)
    {
        $this->uploader = $uploader;
    }

    public function index(Request $request)
    {
        $query = Product::with('category')->withSoldQuantity();

        // 1. Tìm kiếm (tên, thương hiệu, chất liệu, xuất xứ)
        // Bọc trong closure để nhóm các OR lại: nếu không, toán tử OR sẽ "nuốt"
        // luôn điều kiện lọc trạng thái kho ở dưới và trả về kết quả sai.
        if ($request->filled('search')) {
            $keyword = trim($request->search);

            $query->where(function ($sub) use ($keyword) {
                $sub->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('sku', 'like', '%' . $keyword . '%')
                    ->orWhere('brand', 'like', '%' . $keyword . '%')
                    ->orWhere('character_name', 'like', '%' . $keyword . '%')
                    ->orWhere('material', 'like', '%' . $keyword . '%')
                    ->orWhere('origin', 'like', '%' . $keyword . '%');
            });
        }

        // 2. Lọc trạng thái kho (Còn hàng / Hết hàng)
        if ($request->filled('status')) {
            if ($request->status == 'in_stock') {
                $query->where('quantity', '>', 0);
            } elseif ($request->status == 'out_of_stock') {
                $query->where('quantity', '<=', 0);
            }
        }

        // 3. Lọc theo danh mục - chọn danh mục CHA thì lấy luôn sản phẩm của các con
        if ($request->filled('category_id')) {
            $category = Category::find($request->category_id);

            $category
                ? $query->whereIn('category_id', $category->descendantIds())
                : $query->where('category_id', $request->category_id);
        }

        $query->orderBy('id', 'desc');

        $products = $query->paginate(10)->appends($request->query());
        $categories = Category::ordered()->with('parent')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::ordered()->with('parent')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploader->store($request->file('image'), 'products');
        }

        $product = Product::create($data);

        $this->saveVariants($request, $product);
        $this->storeGallery($request, $product);

        return redirect()->route('admin.products.index')->with('success', 'Đã thêm sản phẩm mới!');
    }

    public function show(Product $product)
    {
        $product->load('category');

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::ordered()->with('parent')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request);

        if ($request->hasFile('image')) {
            // Tải ảnh mới lên TRƯỚC rồi mới xóa ảnh cũ: nếu tải lên thất bại
            // (mất mạng, Cloudinary lỗi) thì sản phẩm vẫn còn ảnh cũ để hiển thị.
            $data['image'] = $this->uploader->store($request->file('image'), 'products');
            $this->uploader->delete($product->image);
        }

        // Tick "xóa ảnh hiện tại" mà không chọn ảnh mới
        if (! $request->hasFile('image') && $request->boolean('remove_image')) {
            $this->uploader->delete($product->image);
            $data['image'] = null;
        }

        $product->update($data);

        $this->saveVariants($request, $product);
        $this->storeGallery($request, $product);

        return redirect()->route('admin.products.index')->with('success', 'Cập nhật thành công!');
    }

    public function destroy(Product $product)
    {
        // Sản phẩm đã nằm trong đơn hàng thì không xóa (tránh vỡ lịch sử đơn),
        // admin nên đặt số lượng = 0 để ngừng bán thay vì xóa.
        if ($product->orderItems()->exists()) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Không thể xóa: sản phẩm đã tồn tại trong đơn hàng. Hãy đặt số lượng kho = 0 để ngừng bán.');
        }

        $this->uploader->delete($product->image);

        // Xóa cả file ảnh phụ trên ổ cứng rồi mới xóa bản ghi - làm ngược lại
        // thì mất đường dẫn, file nằm lại trong thư mục uploads mãi mãi.
        foreach ($product->images as $image) {
            $this->uploader->delete($image->path);
        }

        $product->images()->delete();
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Đã xóa sản phẩm.');
    }

    /**
     * Đồng bộ danh sách phân loại gửi từ form với CSDL:
     *  - dòng có id  -> cập nhật; dòng không id -> tạo mới;
     *  - phân loại không còn trong form -> xóa (đơn cũ vẫn giữ tên đã chụp ở order_items.variant_name);
     *  - có phân loại thì products.quantity = tổng tồn các phân loại.
     */
    private function saveVariants(Request $request, Product $product): void
    {
        if (! $request->has('variants') && ! $request->isMethod('put')) {
            return;
        }

        $rows = collect($request->input('variants', []))
            ->filter(fn ($r) => filled($r['name'] ?? null))
            ->values();

        $keepIds = [];
        foreach ($rows as $i => $r) {
            $attrs = [
                'name' => trim($r['name']),
                'price_adjust' => (int) ($r['price_adjust'] ?? 0),
                'quantity' => max(0, (int) ($r['quantity'] ?? 0)),
                'sort_order' => $i,
            ];
            $variant = filled($r['id'] ?? null) ? $product->variants()->find($r['id']) : null;

            if ($variant) {
                $variant->update($attrs);
            } else {
                $variant = $product->variants()->create($attrs);
            }
            $keepIds[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keepIds)->delete();

        if ($rows->isNotEmpty()) {
            $product->syncStockFromVariants();
        }
    }

    /**
     * Lưu các ảnh phụ vừa tải lên vào thư viện của sản phẩm.
     *
     * sort_order tiếp nối số lớn nhất đang có, nên ảnh mới luôn xếp cuối dãy
     * thay vì chen ngang làm đảo lộn thứ tự admin đã sắp.
     */
    private function storeGallery(Request $request, Product $product): void
    {
        if (! $request->hasFile('gallery')) {
            return;
        }

        $order = (int) $product->images()->max('sort_order');

        foreach ($request->file('gallery') as $file) {
            $path = $this->uploader->store($file, 'products');

            if ($path) {
                $product->images()->create(['path' => $path, 'sort_order' => ++$order]);
            }
        }
    }

    /** Xóa một ảnh phụ khỏi thư viện (xóa cả file lẫn bản ghi) */
    public function destroyImage(Product $product, ProductImage $image)
    {
        // Chặn sửa URL để xóa ảnh của sản phẩm khác
        if ((int) $image->product_id !== (int) $product->id) {
            abort(404);
        }

        $this->uploader->delete($image->path);
        $image->delete();

        return back()->with('success', 'Đã xóa ảnh khỏi thư viện.');
    }

    /**
     * Quy tắc kiểm tra dữ liệu dùng chung cho cả thêm mới và cập nhật.
     * Chỉ nhận đúng các trường hợp lệ thay vì $request->all() để tránh lọt dữ liệu rác.
     */
    private function validateProduct(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'variants.*.name' => ['nullable', 'string', 'max:120'],
            'variants.*.price_adjust' => ['nullable', 'integer', 'min:-100000000', 'max:100000000'],
            'variants.*.quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'category_id' => ['required', 'exists:categories,id'],
            'sku' => ['nullable', 'string', 'max:40'],
            'brand' => ['nullable', 'string', 'max:255'],
            'character_name' => ['nullable', 'string', 'max:120'],
            'age_range' => ['nullable', 'string', 'max:255'],
            'age_min_months' => ['nullable', 'integer', 'min:0', 'max:300'],
            'age_max_months' => ['nullable', 'integer', 'min:0', 'max:300', 'gte:age_min_months'],
            'material' => ['nullable', 'string', 'max:255'],
            'origin' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(array_keys(Product::GENDERS))],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:60'],
            'piece_count' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'battery' => ['nullable', 'string', 'max:120'],
            'warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'dimensions' => ['nullable', 'string', 'max:120'],
            'weight_grams' => ['nullable', 'integer', 'min:0', 'max:200000'],
            'safety_note' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            // Giá khuyến mãi bắt buộc phải RẺ HƠN giá gốc. Không chặn ở đây thì
            // khách thấy nhãn "giảm giá" mà lại trả nhiều tiền hơn.
            'sale_price' => ['nullable', 'integer', 'min:0', 'lt:price'],
            'sale_starts_at' => ['nullable', 'date'],
            // 'after:sale_starts_at' CHỈ áp dụng khi admin có nhập ngày bắt đầu.
            //
            // Laravel so sánh bằng strtotime(); ô bắt đầu để trống thì nó nhận
            // được chuỗi rỗng, strtotime('') trả về false và luật LUÔN LUÔN
            // trượt. Hậu quả: đúng cái cách dùng mà giao diện đang hướng dẫn -
            // "để trống = giảm giá ngay lập tức" - lại không lưu được.
            'sale_ends_at' => array_filter([
                'nullable',
                'date',
                $request->filled('sale_starts_at') ? 'after:sale_starts_at' : null,
            ]),
            'quantity' => ['required', 'integer', 'min:0'],
            'image' => ImageUploadService::validationRules(),
            'gallery' => ['nullable', 'array', 'max:8'],
            'gallery.*' => ImageUploadService::validationRules(),
        ], ImageUploadService::validationMessages() + [
            'age_max_months.gte' => 'Tuổi tối đa phải lớn hơn hoặc bằng tuổi tối thiểu.',
            'sale_price.lt' => 'Giá khuyến mãi phải nhỏ hơn giá bán gốc.',
            'sale_ends_at.after' => 'Thời điểm kết thúc phải sau thời điểm bắt đầu.',
        ], [
            'name' => 'Tên sản phẩm',
            'category_id' => 'Danh mục',
            'sku' => 'Mã sản phẩm',
            'brand' => 'Thương hiệu',
            'character_name' => 'Nhân vật / chủ đề',
            'age_min_months' => 'Tuổi tối thiểu',
            'age_max_months' => 'Tuổi tối đa',
            'piece_count' => 'Số chi tiết',
            'warranty_months' => 'Bảo hành',
            'weight_grams' => 'Cân nặng',
            'price' => 'Giá bán',
            'sale_price' => 'Giá khuyến mãi',
            'sale_starts_at' => 'Bắt đầu khuyến mãi',
            'sale_ends_at' => 'Kết thúc khuyến mãi',
            'quantity' => 'Số lượng kho',
            'image' => 'Hình ảnh',
            'gallery' => 'Thư viện ảnh',
        ]);

        // 'gallery' là các file tải lên, không phải cột của bảng products
        unset($data['gallery'], $data['variants']);

        // Bỏ trống giá khuyến mãi = tắt chương trình, dọn luôn hai mốc thời gian
        // để không còn dữ liệu mồ côi gây khó hiểu khi xem lại.
        if (blank($data['sale_price'] ?? null)) {
            $data['sale_price'] = null;
            $data['sale_starts_at'] = null;
            $data['sale_ends_at'] = null;
        }

        // Checkbox kỹ năng gửi lên dạng mảng -> gộp thành chuỗi để lưu 1 cột
        $data['skills'] = ! empty($data['skills']) ? implode(', ', $data['skills']) : null;

        // Nổi bật là checkbox nên không có trong $data khi không tick
        $data['is_featured'] = $request->boolean('is_featured');

        // Tự sinh nhãn độ tuổi nếu admin chỉ nhập số tháng
        if (blank($data['age_range'] ?? null)) {
            $data['age_range'] = $this->buildAgeLabel($data['age_min_months'] ?? null, $data['age_max_months'] ?? null);
        }

        return $data;
    }

    /**
     * "36 tháng - 72 tháng" -> "3 - 6 tuổi". Dưới 24 tháng thì giữ đơn vị tháng
     * cho đúng cách nói của phụ huynh ("đồ chơi cho bé 6 tháng").
     */
    private function buildAgeLabel(?int $min, ?int $max): ?string
    {
        if ($min === null && $max === null) {
            return null;
        }

        $format = fn (int $months) => $months < 24 ? $months . ' tháng' : intdiv($months, 12) . ' tuổi';

        if ($max === null) {
            return $format((int) $min) . ' trở lên';
        }

        if ($min === null) {
            return 'Dưới ' . $format((int) $max);
        }

        return $format($min) . ' - ' . $format($max);
    }
}
