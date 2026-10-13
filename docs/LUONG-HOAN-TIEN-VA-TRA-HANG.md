# Luồng hoàn tiền, Ví ToyLand và trả hàng (bản đã triển khai)

Tài liệu này mô tả đúng những gì code đang làm. Phương án phân tích ban đầu nằm ở `PHUONG-AN-HOAN-TIEN-VA-COD.md`.

## 1. Quyết định đã chốt

1. Trạng thái COD mới: **"Đã thu hộ – chờ đối soát"** (`cod_collected`).
2. Phạm vi: sửa COD, Ví ToyLand, yêu cầu hoàn tiền, trả hàng sau khi nhận, thanh toán bằng ví ở checkout.
3. **Ví chỉ là một phương thức thanh toán TRỌN đơn**: chọn được khi số dư ≥ tổng đơn (đã gồm phí ship). Không trả một phần, không kèm COD/MoMo, đúng thực tế các sàn.
4. Mọi khoản hoàn đều về **Ví ToyLand** của khách. Ví không rút ra tiền mặt.

## 2. Thanh toán COD (đã sửa lỗi luồng)

`pending` (chờ giao) → shipper giao thành công, hệ thống tự chuyển → `cod_collected` (khách đã trả, tiền ở shipper) → admin đối soát khớp → `paid` (tiền về shop). Nếu lệch: `failed` rồi có thể về `paid` sau khi xử lý xong.

Trang Giao dịch thanh toán chỉ còn cho admin làm đúng các bước trên, chỉ khi đơn đã giao; không thể đặt tay "chờ hoàn tiền" hay "đã hoàn tiền" nữa. Hoàn tiền chỉ phát sinh từ sự kiện thật (hủy đơn, trả hàng, báo bị trừ tiền) và đi qua hộp thư Hoàn tiền.

## 3. Bốn loại yêu cầu hoàn tiền (cùng một bảng `refund_requests`)

| Loại | Ai tạo | Khi nào | Xử lý |
|---|---|---|---|
| Đơn đã thanh toán bị hủy | Hệ thống | Hủy/hết hạn/hoàn hàng khi khách đã trả (MoMo, hoặc COD đã thu hộ), hoặc MoMo báo tiền về sau khi đơn đã hủy | Admin bấm "Hoàn vào ví". Đơn trả bằng ví thì hoàn ngay, không cần duyệt |
| Bị trừ tiền nhưng đơn chưa ghi nhận | Khách | Đơn MoMo đang giữ hàng hoặc đã hủy; khách nhập mã giao dịch MoMo | Đơn đang giữ hàng: admin đối chiếu MoMo, thấy tiền thì "Ghi nhận đã nhận tiền" để đơn đi tiếp. Đơn đã hủy: hoàn vào ví. Không có giao dịch: từ chối kèm lý do |
| Thanh toán trùng | Hệ thống | MoMo báo thêm một khoản cho đơn đã có khoản thanh toán | Admin hoàn khoản thứ hai vào ví, khoản gốc vẫn hợp lệ |
| Trả hàng sau khi nhận | Khách | Xem mục 4 | Xem mục 4 |

Bảo đảm: mỗi khoản chỉ hoàn một lần (khóa dòng + kiểm trạng thái), số dư ví luôn bằng tổng sổ cái `wallet_transactions`, khoản do hệ thống tạo thì shop không từ chối được.

## 4. Trả hàng sau khi nhận (mô phỏng thực tế)

Điều kiện: đơn "Đã giao thành công", trong **7 ngày** từ lúc nhận hàng, trả cả đơn, mỗi đơn một yêu cầu đang xử lý.

Lý do và ai chịu phí:

- Lỗi từ shop (sản phẩm lỗi, giao sai, thiếu hàng, không giống mô tả): hoàn **100%**, shop chịu phí gửi trả. Sản phẩm lỗi mặc định không nhập lại kho.
- Khách đổi ý: hoàn **tổng đơn trừ phí vận chuyển chiều đi**; khách tự chịu phí gửi hàng về.

Các bước:

1. **Khách gửi yêu cầu** (chọn lý do, mô tả tình trạng). Chưa gửi hàng, đơn vẫn "Đã giao".
2. **Shop duyệt** (hoặc từ chối kèm lý do). Khách nhận địa chỉ kho và hạn gửi hàng 7 ngày.
3. **Khách gửi hàng về**, nhập đơn vị vận chuyển + mã vận đơn. Quá hạn thì yêu cầu tự hủy.
4. **Shop nhận hàng**, bắt đầu kiểm tra.
5. **Kết quả kiểm hàng**:
   - Đạt: tiền vào ví khách, đơn chuyển "Đã hoàn hàng", thu hồi điểm thưởng và chi tiêu tích lũy của đơn, nhập lại kho nếu shop đánh dấu còn bán được.
   - Không đạt: không hoàn tiền, ghi rõ lý do, shop gửi trả hàng cho khách.

**Tiền chỉ hoàn sau bước 5.** Khách có thể hủy yêu cầu trước khi gửi hàng.

## 5. Thanh toán bằng Ví ToyLand

- Hiện ở bước thanh toán khi ví có số dư; bị khóa khi số dư chưa đủ trả cả đơn.
- Trừ ví, tạo đơn, trừ kho nằm trong **một giao dịch CSDL**: lỗi ở đâu hoàn tác ở đó.
- Đơn trả bằng ví vào hàng chờ xác nhận, đã ghi nhận thanh toán. Nếu đơn bị hủy, số tiền **tự hoàn lại vào ví ngay**.

## 6. Nơi thao tác

- Khách: menu tài khoản → **Ví ToyLand** (số dư, sổ cái, các yêu cầu); trang chi tiết đơn có khối "Báo bị trừ tiền" (MoMo) và "Trả hàng".
- Admin: menu **Hoàn tiền & trả hàng** (có số yêu cầu đang chờ), lọc theo trạng thái/loại/đơn.

## 7. Cấu hình (`.env`, đều có mặc định)

`RETURN_WINDOW_DAYS=7`, `RETURN_SHIP_BACK_DAYS=7`, `RETURN_ADDRESS="Kho ToyLand - ..."`.

Sau khi cập nhật code cần chạy `php artisan migrate` (tạo `wallet_transactions`, `refund_requests`, cột `users.wallet_balance`).

## 8. Kiểm thử

`php artisan test --filter=RefundFlowTest` (17 ca nghiệp vụ), `RefundPagesTest` (khách → admin qua HTTP), `CodReconcileTest` (đối soát COD).
