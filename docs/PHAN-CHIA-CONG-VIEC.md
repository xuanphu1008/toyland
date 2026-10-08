# Phân chia công việc nhóm 5 thành viên: dự án ToyLand

Phạm vi: **toàn bộ phần kỹ thuật** (mã nguồn, luồng nghiệp vụ, dữ liệu, kiểm thử, triển khai, demo). **Không gồm việc viết báo cáo.**

> Thay các chỗ `[Tên]` bằng tên thật. Thứ tự các thành viên chỉ để đánh số, không phải thứ bậc.

---

## 1. Tổng quan phân công

| TV | Mảng phụ trách | Phụ trách chính | Quy mô |
|---|---|---|---|
| **TV1** `[Tên]` | Nền tảng, tài khoản, triển khai | Đăng ký, đăng nhập, phân quyền, hồ sơ; cơ sở dữ liệu; Docker, Render, Aiven, Cloudinary, Brevo, cron; quản lý Git | Vừa + hạ tầng |
| **TV2** `[Tên]` | Sản phẩm và gợi ý | Sản phẩm, danh mục, tìm kiếm, bộ lọc, tồn kho, flash sale, đánh giá, yêu thích, so sánh, gợi ý, **biến thể sản phẩm** | Lớn |
| **TV3** `[Tên]` | Giỏ hàng, đặt hàng, thanh toán | Giỏ hàng, thanh toán, địa chỉ và phí GHN, mã giảm giá, điểm thưởng và hạng, MoMo, trang Tài chính | Lớn |
| **TV4** `[Tên]` | Luồng đơn hàng và vận chuyển | Vòng đời đơn, vận đơn, màn hình vận chuyển, xử lý hàng loạt, hoàn hàng, hóa đơn, thống kê, xuất dữ liệu | Lớn |
| **TV5** `[Tên]` | Hỗ trợ, nội dung, cá nhân hóa | Chatbot và Gemini, chat trực tiếp, ticket, câu hỏi thường gặp, trang tĩnh, tin tức, hồ sơ bé, nhắc sinh nhật, thông báo | Lớn |

Nguyên tắc chia: mỗi người sở hữu **một cụm tệp riêng** (controller, service, model, view), nên rất ít khi sửa chung một tệp và ít xung đột khi gộp mã. Điểm giao nhau giữa các cụm được liệt kê ở mục 4.

---

## 2. Chi tiết từng thành viên

### TV1: Nền tảng, tài khoản và triển khai

**Chức năng đảm nhận (đối chiếu đề bài)**
- Quản lý người dùng: đăng ký, đăng nhập, xác thực người dùng, quản lý hồ sơ, phân quyền khách hàng và quản trị viên.
- Tích hợp mạng xã hội: đăng nhập Google.
- Hạ tầng và triển khai (yêu cầu chung).

**Mã nguồn sở hữu**
| Nhóm | Tệp, thư mục |
|---|---|
| Xác thực | `AuthController`, `SocialAuthController`, `ProfileController`, middleware admin, `resources/views/auth`, `profile` |
| Quản trị người dùng | `Admin/UserController`, `resources/views/admin/users` |
| Dữ liệu | Toàn bộ `database/migrations`, `database/seeders` (đảm bảo mọi bảng có khóa chính ngay lúc tạo vì Aiven bắt buộc) |
| Hệ thống | `SystemController` (kiểm tra sống, cron), `Console/Kernel`, `DeploySeedCommand`, `TrustProxies`, `AppServiceProvider` |
| Triển khai | `Dockerfile`, `docker/`, `render.yaml`, `.env.example`, `DEPLOY.md`, `.gitignore`, `.dockerignore` |
| Ảnh | `ImageUploadService` (Cloudinary) |
| Kiểm thử | `tools/smoke_test.py` |

**Việc cần làm**
1. Giữ cho `migrate:fresh --seed` luôn chạy sạch trên cả MySQL máy nhà lẫn Aiven; **rà mọi migration mới của nhóm** trước khi gộp.
2. Quản lý Git: kho **Private**, bảo vệ nhánh `main`, kiểm tra `git status` không lẫn `.env` hay tệp cá nhân.
3. Duy trì triển khai trên Render; cập nhật biến môi trường khi có tính năng mới; cấu hình cron-job.org.
4. Soát bảo mật chung: mật khẩu quản trị mạnh, khóa API không lộ, thu hồi khóa cũ đã lộ.
5. Chạy `smoke_test.py` trước mỗi lần chốt bản.

**Bàn giao:** trang đăng ký, đăng nhập, Google chạy được trên web thật; bản triển khai ổn định; kịch bản dựng lại hệ thống từ đầu.

---

### TV2: Sản phẩm, đánh giá và gợi ý

**Chức năng đảm nhận**
- Quản lý sản phẩm: hiển thị chi tiết, sản phẩm bán chạy, hệ thống danh mục, bộ lọc, tìm kiếm theo từ khóa và thuộc tính, quản lý tồn kho.
- Hệ thống phản hồi và đánh giá.
- Cá nhân hóa: lịch sử duyệt, gợi ý sản phẩm, danh sách yêu thích.
- So sánh sản phẩm.

**Mã nguồn sở hữu**
| Nhóm | Tệp, thư mục |
|---|---|
| Sản phẩm | `ProductController`, `HomeController`, `CategoryController`, `Models/Product`, `Category`, `ProductImage`, `ProductView` |
| Giao diện | `resources/views/products`, `welcome.blade.php`, `compare`, `wishlist`, `admin/products`, `admin/categories` |
| Đánh giá | `ReviewController`, `Admin/ReviewController`, `Models/Review`, `resources/views/reviews`, `admin/reviews` |
| Yêu thích, so sánh | `WishlistController`, `CompareController` |
| Gợi ý | `RecommendationService` |
| Dữ liệu mẫu | `ToyCatalogSeeder` |

**Việc cần làm**
1. **Biến thể sản phẩm** (màu, kích cỡ, mẫu): thiết kế bảng biến thể, tồn kho theo từng biến thể, giá riêng nếu có; sửa trang chi tiết, giỏ hàng và trang quản trị.
   - Chốt đã thống nhất: **flash sale áp dụng cho cả sản phẩm**, không theo biến thể.
2. **Gộp hoặc xóa các cặp sản phẩm trùng** trong dữ liệu mẫu, cập nhật seeder để chạy lại không nhân bản.
3. Rà soát tìm kiếm và bộ lọc theo từ khóa, độ tuổi, thương hiệu, nhân vật, giá.
4. Đảm bảo chỉ khách có đơn **đã giao thành công** mới đánh giá được.

**Phối hợp:** biến thể ảnh hưởng giỏ hàng và trừ kho, **làm cùng TV3 và TV4** (xem mục 4).

**Bàn giao:** danh sách và trang chi tiết có biến thể; seed sạch không trùng; tìm kiếm và lọc đúng.

---

### TV3: Giỏ hàng, đặt hàng và thanh toán

**Chức năng đảm nhận**
- Giỏ hàng: thêm, xóa, cập nhật số lượng; tiến hành đặt hàng.
- Thanh toán: COD, ví MoMo (và chuyển khoản nếu mở rộng).
- Mã giảm giá, voucher; chương trình khách hàng thân thiết (tích điểm, hạng thành viên).
- Tích hợp vận chuyển: tỉnh, huyện, xã và phí GHN.
- Trang Tài chính cho quản trị.

**Mã nguồn sở hữu**
| Nhóm | Tệp, thư mục |
|---|---|
| Giỏ hàng, đặt hàng | `CartController`, `CheckoutController`, `resources/views/cart`, `checkout` |
| Địa chỉ, phí | `GHNService`, `UserAddress`, các route `locations` |
| MoMo | `MomoController`, `MomoService`, `PaymentTransaction` |
| Khuyến mãi | `CouponController`, `Admin/CouponController`, `Models/Coupon`, `CouponUsage`, `resources/views/coupons`, `admin/coupons` |
| Thành viên | `PointTransaction`, hạng thành viên trong `Models/User` |
| Tài chính | `Admin/FinanceController`, `Mail/PaymentStatusChangedMail`, `resources/views/admin/finance` |

**Việc cần làm**
1. Giữ đúng nguyên tắc: **phí vận chuyển luôn tính lại ở máy chủ**, giá luôn lấy từ giá hiệu lực của sản phẩm, kho bị trừ ngay trong giao dịch cơ sở dữ liệu.
2. Hoàn thiện đường **MoMo hai chiều** (callback và IPN dùng chung một hàm, không ghi nhận hai lần, tiền về muộn thì chờ hoàn tiền).
3. Mã giảm giá: kiểm tra điều kiện, giới hạn lượt dùng, không dùng trùng.
4. Trang Tài chính: chỉ cho chuyển trạng thái thanh toán hợp lệ, khóa chống hai người sửa cùng lúc, xuất tệp có đủ bộ lọc.
5. Cập nhật giỏ hàng và đặt hàng khi **TV2 làm biến thể**.

**Bàn giao:** đặt hàng COD và MoMo chạy trơn trên web thật; kiểm thử với thẻ thử MoMo; giỏ hàng đúng giá khi flash sale hết hạn.

---

### TV4: Luồng đơn hàng, vận chuyển và thống kê

**Chức năng đảm nhận**
- Quản lý đơn hàng: theo dõi trạng thái từ lúc đặt tới khi giao thành công, lịch sử đơn của khách, quản lý đơn cho quản trị (xác nhận, hủy, cập nhật).
- Hệ thống vận chuyển, **mã vận đơn**, giao thất bại, hoàn hàng.
- Thống kê doanh số: doanh thu, sản phẩm bán chạy.
- Email xác nhận đơn và thông báo cập nhật.

**Mã nguồn sở hữu**
| Nhóm | Tệp, thư mục |
|---|---|
| Lõi luồng đơn | `Services/Orders/OrderWorkflow`, `WorkflowException` |
| Vận chuyển | `Services/Shipping/*`, `GHNOrderService`, `Admin/CarrierController`, `resources/views/admin/carrier` |
| Đơn hàng | `OrderController` (khách), `Admin/OrderController`, `Models/Order`, `OrderItem`, `OrderStatusLog`, `resources/views/orders`, `admin/order` |
| Tự hủy quá hạn | `Console/Commands/ReleaseExpiredOrdersCommand` |
| Email đơn | `Mail/OrderPlacedMail`, `OrderStatusChangedMail`, `resources/views/emails` |
| Thống kê | `Admin/ReportController`, `Admin/ExportController`, `CsvExportService`, `AdminController` (bảng điều khiển) |
| Kiểm thử | `tests/Feature/OrderWorkflowTest.php` |
| Tài liệu luồng | `docs/LUONG-DON-HANG.md` |

**Việc cần làm**
1. Giữ `OrderWorkflow` là **nơi duy nhất** đổi trạng thái: mọi người khác muốn đổi trạng thái đơn phải gọi qua đây.
2. Kiểm thử đủ các kịch bản: COD giao thành công, giao thất bại 3 lần rồi hoàn, MoMo trả tiền rồi hủy, MoMo quá hạn, tiền về muộn, khách tự hủy, xử lý hàng loạt có đơn sai bước.
3. Màn hình vận chuyển và xử lý hàng loạt: thao tác nhanh, báo rõ đơn nào lỗi và lý do.
4. Nếu muốn dùng GHN thật (`SHIPPING_CARRIER=ghn`): làm webhook nhận trạng thái giao nhận (hiện chưa làm).
5. Báo cáo doanh thu: đối chiếu số liệu với dữ liệu đơn đã giao.
6. Cập nhật luồng khi **TV2 làm biến thể** (trả kho theo biến thể khi hủy, hoàn).

**Bàn giao:** kịch bản kiểm thử bằng tay chạy đúng trên web thật; bộ kiểm thử tự động xanh; thống kê đúng.

---

### TV5: Hỗ trợ, nội dung và cá nhân hóa

**Chức năng đảm nhận**
- Hỗ trợ khách hàng: câu hỏi thường gặp, chat trực tiếp, hệ thống ticket.
- Chatbot tư vấn (Gemini).
- Quản lý nội dung: trang tĩnh (giới thiệu, liên hệ, chính sách), blog và tin tức.
- Cá nhân hóa: hồ sơ của bé, gợi ý quà, sổ sinh nhật.
- Thông báo trong ứng dụng; email nhắc sinh nhật.

**Mã nguồn sở hữu**
| Nhóm | Tệp, thư mục |
|---|---|
| Chatbot | `Services/Chatbot/*` (`ChatbotService`, `IntentMatcher`, `EntityExtractor`, `TextAnalyzer`, `LlmClient`, `LlmGuard`, `LlmQuota`), `ChatbotController`, `Admin/ChatbotController`, `resources/views/admin/chatbot` |
| Chat trực tiếp | `User/ChatController`, `Admin/ChatController`, `Models/Message` |
| Ticket | `TicketController`, `Admin/TicketController`, `Models/Ticket`, `TicketReply`, `resources/views/tickets`, `admin/tickets` |
| Câu hỏi thường gặp | `FaqController`, `Admin/FaqController`, `Models/Faq` |
| Nội dung | `PageController`, `PostController`, `Admin/PageController`, `Admin/PostController`, `resources/views/pages`, `posts`, `admin/pages`, `admin/posts` |
| Hồ sơ bé | `ChildProfileController`, `Models/ChildProfile`, `GiftFinderService`, `Admin/BirthdayController`, `BirthdayReminderCommand`, `Mail/BirthdayReminderMail`, `resources/views/children`, `admin/birthdays` |
| Thông báo | `NotificationController`, `Models/UserNotification`, `resources/views/notifications` |
| Dữ liệu mẫu | `ContentSeeder` |

**Việc cần làm**
1. Chatbot: giữ **bốn lớp bảo vệ** (bot nội bộ trước, lọc chủ đề, chặn câu lệnh độc, che thông tin cá nhân) và hạn mức gọi AI thấp hơn trần của Google. **Không xoay vòng nhiều khóa để lách hạn mức.**
2. Mở rộng bot nội bộ cho các câu hỏi về đơn hàng, vận chuyển, hoàn hàng, biến thể sản phẩm (sau khi TV2 xong).
3. Rà soát nội dung trang chính sách (đổi trả, vận chuyển, thanh toán) cho khớp luồng đơn thực tế.
4. Kiểm thử ticket và chat trực tiếp giữa khách và quản trị.

**Bàn giao:** chatbot trả lời đúng, từ chối đúng câu ngoài chủ đề; nhắc sinh nhật chạy qua cron; nội dung tĩnh đầy đủ.

---

## 3. Đối chiếu đề bài với người phụ trách

| Yêu cầu của đề | Người làm |
|---|---|
| **Chính:** hiển thị sản phẩm, bán chạy | TV2 |
| **Chính:** danh mục, bộ lọc, tìm kiếm | TV2 |
| **Chính:** quản lý tồn kho (trừ khi mua, cộng khi hủy) | TV2 (cấu trúc) + TV3 (trừ khi đặt) + TV4 (cộng khi hủy, hoàn) |
| **Chính:** đăng ký, đăng nhập, xác thực | TV1 |
| **Chính:** quản lý hồ sơ | TV1 |
| **Chính:** phân quyền khách và quản trị | TV1 |
| **Chính:** giỏ hàng, đặt hàng | TV3 |
| **Chính:** thanh toán (ví điện tử, chuyển khoản, thẻ) | TV3 |
| **Chính:** theo dõi trạng thái đơn, lịch sử đơn, quản lý đơn cho quản trị | TV4 |
| **Chính:** đánh giá sau khi đơn thành công | TV2 |
| **Chính:** thống kê doanh số, sản phẩm bán chạy | TV4 |
| **Phụ:** lịch sử duyệt, gợi ý, yêu thích | TV2 |
| **Phụ:** mã giảm giá | TV3 |
| **Phụ:** khách hàng thân thiết | TV3 |
| **Phụ:** email, thông báo | TV4 (email đơn) + TV5 (thông báo, nhắc sinh nhật) |
| **Phụ:** câu hỏi thường gặp, chat, ticket | TV5 |
| **Phụ:** quản lý nội dung, blog | TV5 |
| **Phụ:** tích hợp vận chuyển | TV3 (tính phí) + TV4 (vận đơn) |
| **Phụ:** đăng nhập mạng xã hội | TV1 |
| **Phụ:** phân tích hành vi người dùng | TV2 (dữ liệu xem sản phẩm) + TV4 (thống kê) |
| **Chung:** triển khai, hạ tầng | TV1 |

---

## 4. Điểm giao nhau: ai phải bàn với ai

| Chủ đề | Bên liên quan | Quy ước |
|---|---|---|
| **Đổi trạng thái đơn** | TV3, TV4 | Chỉ gọi `OrderWorkflow`. TV3 (MoMo) gọi `paymentReceived`; không ai tự `update status` trong controller. |
| **Trừ và cộng kho** | TV2, TV3, TV4 | TV3 trừ lúc đặt; TV4 cộng lại đúng một lần qua `restocked_at`. TV2 giữ cấu trúc số lượng. |
| **Biến thể sản phẩm** | TV2, TV3, TV4, TV5 | TV2 chủ trì thiết kế bảng và gửi cho cả nhóm duyệt **trước khi code**; sau đó TV3 sửa giỏ hàng, TV4 sửa hoàn kho, TV5 sửa chatbot. |
| **Bảng dữ liệu mới** | Tất cả, TV1 duyệt | Mọi migration mới: bảng phải có khóa chính trong lệnh tạo (`$table->id()`); TV1 chạy thử trên MySQL trước khi gộp. |
| **Biến môi trường mới** | Tất cả, TV1 cập nhật | Người thêm biến báo TV1 để cập nhật `render.yaml`, `.env.example`, `DEPLOY.md`. |
| **Giá bán** | TV2, TV3 | Chỉ dùng `effective_price` của sản phẩm để tính tiền, không đọc thẳng `price`. |
| **Email, thông báo** | TV4, TV5 | Gửi **sau khi** dữ liệu đã chốt; lỗi gửi thư chỉ ghi nhật ký, không làm hỏng thao tác. |
| **Điểm thưởng** | TV3, TV4 | TV3 sở hữu quy tắc điểm và hạng; cộng điểm xảy ra trong `OrderWorkflow` khi giao thành công. |

---

## 5. Quy trình làm việc chung

**Git**
1. Mỗi người làm trên nhánh riêng: `tv1/xac-thuc`, `tv2/bien-the`, `tv3/momo`... Không đẩy thẳng lên `main`.
2. Tạo Pull Request; **ít nhất một người khác** đọc trước khi gộp (ưu tiên người có cụm giao nhau, xem mục 4).
3. Trước khi tạo PR: chạy `php artisan test` và `python tools/smoke_test.py http://127.0.0.1:8000`; cả hai phải sạch lỗi.
4. Commit nhỏ, thông điệp rõ ("TV3: sửa MoMo callback trùng"), không gộp nhiều việc vào một commit.
5. Mỗi lần gộp vào `main`, Render tự deploy; **TV1 kiểm tra bản chạy thật** sau khi deploy.
6. Không bao giờ commit `.env`, khóa API, tệp ghi chú cá nhân, `.docx`.

**Môi trường máy mỗi người**
- Laragon, MySQL trên máy (`root`, mật khẩu trống), cơ sở dữ liệu `toyland`.
- Sau khi kéo mã mới: `composer install`, `php artisan migrate`; khi cần dữ liệu sạch: `php artisan migrate:fresh --seed`.
- Tài khoản mẫu: quản trị `admin@tpcn.com / Admin@123`, khách `khachhang1@tpcn.com / Khach@123`.

**Định nghĩa "xong" cho mỗi việc**
- Chạy đúng trên máy và trên web thật.
- Có kiểm thử (tự động nếu là nghiệp vụ, hoặc bước thử tay ghi rõ).
- Không làm hỏng phần của người khác (kết quả `smoke_test.py` sạch).
- Mô tả ngắn trong PR: đã đổi gì, cách thử ra sao.

---

## 6. Gợi ý lộ trình

| Giai đoạn | Việc chính | Người dẫn dắt |
|---|---|---|
| **1. Chốt thiết kế biến thể** | Thống nhất bảng biến thể, ảnh hưởng tới giỏ hàng, kho, hoàn hàng, chatbot | TV2 (cả nhóm duyệt) |
| **2. Làm song song** | Mỗi người hoàn thiện cụm của mình; TV2 làm biến thể và gộp sản phẩm trùng | Tất cả |
| **3. Nối ghép** | TV3, TV4, TV5 sửa phần phụ thuộc biến thể; TV1 rà migration và biến môi trường | TV3, TV4, TV5, TV1 |
| **4. Kiểm thử chéo** | Mỗi người thử cụm của người khác theo kịch bản ở mục 7; sửa lỗi | Tất cả |
| **5. Chốt bản** | Đóng băng tính năng, deploy bản cuối, chạy `smoke_test.py` trên web thật, tập demo | TV1 dẫn, tất cả tham gia |

---

## 7. Kiểm thử chéo (ai thử phần của ai)

Để tránh "tự thấy ổn", mỗi người thử cụm của một người khác:

| Người thử | Thử cụm của | Kịch bản chính |
|---|---|---|
| TV1 | TV3 | Đặt đơn COD và MoMo bằng thẻ thử, kiểm tra tiền và kho |
| TV2 | TV4 | Đi hết vòng đời đơn bằng màn hình vận chuyển, kiểm tra kho cộng lại khi hoàn |
| TV3 | TV5 | Hỏi chatbot về đơn hàng, thử câu ngoài chủ đề, tạo ticket và chat |
| TV4 | TV1 | Đăng ký, xác thực email, đăng nhập Google, phân quyền (khách vào địa chỉ quản trị) |
| TV5 | TV2 | Tìm kiếm, lọc, biến thể, đánh giá chỉ sau khi đơn giao thành công |

---

## 8. Kịch bản demo (phân vai trình bày)

Mỗi người trình bày đúng cụm mình làm, theo thứ tự một khách hàng đi qua hệ thống:

1. **TV1:** giới thiệu kiến trúc triển khai; đăng ký, xác thực email, đăng nhập (kể cả Google); phân quyền.
2. **TV2:** xem sản phẩm, lọc, tìm kiếm, chọn biến thể, flash sale, yêu thích, so sánh.
3. **TV3:** thêm giỏ hàng, áp mã giảm giá, chọn địa chỉ (phí GHN), thanh toán COD và MoMo.
4. **TV4:** quản trị xác nhận hàng loạt, đóng gói và in phiếu, màn hình vận chuyển (giao thành công, thất bại, hoàn hàng), thống kê doanh số.
5. **TV5:** chatbot (kể cả câu ngoài chủ đề), ticket, hồ sơ bé và sổ sinh nhật, tin tức.

Khách quay lại xem đơn, nhận thông báo và email ở mỗi bước, rồi đánh giá sản phẩm (TV2) và được cộng điểm (TV3, TV4).
