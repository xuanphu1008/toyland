# Phương án xử lý: trạng thái thu tiền COD và hoàn tiền (ví ToyLand)

> **Cập nhật:** bản đã triển khai (đã chốt: ví chỉ trả trọn đơn, có luồng trả hàng) xem `LUONG-HOAN-TIEN-VA-TRA-HANG.md`.


Nguồn: file `tk.md` do bạn ghi. Mục 1 và 2 được phân tích bên dưới. **Mục 3 trong `tk.md` đang để trống**, bạn bổ sung rồi mình phân tích tiếp.

Đối chiếu với mã hiện tại: `Admin/FinanceController`, `Services/Orders/OrderWorkflow`, `Services/MomoService`, `MomoController`, `Models/Order`, trang `admin/finance/transactions`.

---

## 1. Lỗi luồng: Admin đổi "Đã thanh toán" thành "Chờ hoàn tiền" tùy ý

### 1.1 Hiện trạng (nguyên nhân gốc)

Trang Tài chính có ô "Cập nhật COD" cho phép admin tự chọn trạng thái thanh toán. Bảng chuyển trạng thái cho phép:

```
pending        -> pending, paid, failed
failed         -> failed, pending, paid
paid           -> paid, refund_pending        <-- chỗ sai
refund_pending -> refund_pending, refunded
```

Vấn đề không nằm ở một dòng cấu hình mà ở cách nghĩ: **trạng thái thanh toán đang là một ô tự do admin muốn chọn gì cũng được**, trong khi nó phải là *hệ quả của một sự kiện có thật*. Cụ thể:

1. Bước `paid -> refund_pending` không kiểm tra vòng đời đơn. Đơn COD đã giao xong (`completed`) vẫn bị chuyển sang "Chờ hoàn tiền" mà không có hủy đơn, hoàn hàng hay yêu cầu nào của khách.
2. Không có **lý do** và **người yêu cầu**. Về sau không biết khoản "chờ hoàn" này do hủy đơn, hoàn hàng, tiền về muộn hay do admin bấm nhầm.
3. Hoàn tiền chỉ là đổi nhãn. Không có số tiền thực hoàn, không có nơi tiền đi tới, không ghi sổ.
4. Đơn COD chỉ có hai pha "chưa thu / đã thu", trong khi thực tế COD có hai bước tiền khác nhau (xem 1.3).
5. Hậu quả sang báo cáo: đơn đã giao nhưng bị gán "chờ hoàn" làm lệch doanh thu (hiện có 3 công thức doanh thu khác nhau, xem `PHAN-TICH-CHI-TIET-THANH-VIEN.md`).

### 1.2 Nguyên tắc sửa

> Trạng thái thanh toán chỉ đổi do **sự kiện hệ thống** hoặc **một yêu cầu đã được duyệt**. Admin không chọn tự do trạng thái "hoàn tiền".

- `refund_pending` chỉ được sinh ra từ 3 nguồn: (a) hủy đơn / hoàn hàng sau khi đã thu tiền (hệ thống tự làm, `OrderWorkflow` đã có), (b) tiền MoMo về muộn sau khi đơn hết hạn, (c) **khách gửi yêu cầu hoàn tiền và admin duyệt** (mục 2).
- Xóa `refund_pending` và `refunded` khỏi danh sách chọn tay ở trang Tài chính. Hoàn tiền chỉ làm ở màn "Hoàn tiền" (mục 2).
- Ô cập nhật tay chỉ còn việc đối soát COD.

### 1.3 Có nên thêm trạng thái "Chờ xác nhận" mặc định cho COD không?

**Có, nên làm**, nhưng gọi đúng nghĩa là **"Đã thu hộ - chờ đối soát"** (mã `cod_collected`), vì nó khớp đúng thực tế COD:

| Pha | Ai giữ tiền | Trạng thái |
|---|---|---|
| Chưa giao | Khách | `pending` Chưa thu |
| Giao thành công, shipper thu tiền mặt | **Shipper** | `cod_collected` Đã thu hộ, chờ đối soát |
| Shipper/GHN chuyển tiền về shop, admin đối chiếu | **Shop** | `paid` Đã thanh toán |

Lợi ích: hiện `OrderWorkflow::collectCod` đặt thẳng `paid` ngay khi shipper giao xong, coi như shop đã có tiền, dù tiền còn nằm ở shipper vài ngày. Tách ra thì trang Tài chính trả lời được "tiền đang nằm ở đâu".

Quy tắc mặc định (đây là câu trả lời cho ý "mặc định"):

- Khi giao thành công, hệ thống tự đặt `cod_collected`. Ô cập nhật COD của admin **mặc định hiển thị đúng trạng thái này**, nút chính là "Xác nhận đã nhận tiền đối soát".
- Admin chỉ có 2 lựa chọn hợp lệ ở `cod_collected`: `paid` (đối soát khớp) hoặc `failed` (shipper báo thiếu, giữ để xử lý).
- Hoàn tiền không còn là lựa chọn ở đây.

Giá: một giá trị chuỗi mới trong cột `payment_status` (cột là chuỗi nên không cần đổi cấu trúc). Phải thêm nhãn ở `Order::PAYMENT_LABELS`, `FinanceController::STATUSES`, view tài chính, email.

### 1.4 Sơ đồ trạng thái thanh toán sau khi sửa

```
COD
  pending ──(giao thành công)──▶ cod_collected ──(admin đối soát khớp)──▶ paid
     │                                │                                      │
     │                                └──(thiếu tiền)──▶ failed              │
     └──(hủy đơn)──▶ cancelled                                               │
                                                                              │
                  (khách yêu cầu hoàn tiền, admin duyệt) ◀───────────────────┘
                                         │
                                  refund_pending ──(ghi có ví)──▶ refunded

MoMo
  initiated ──(MoMo báo ok)──▶ paid ──(hủy/hoàn hàng/yêu cầu duyệt)──▶ refund_pending ──▶ refunded
      │                          ▲
      └──(thất bại)──▶ failed    └──(tiền về muộn sau khi đơn hết hạn)── cũng vào refund_pending
```

Điều kiện chặn (kiểm tra ở server, không chỉ ẩn nút):

| Chuyển | Điều kiện bắt buộc |
|---|---|
| `pending -> cod_collected` | chỉ hệ thống, khi đơn `completed`/đã giao |
| `cod_collected -> paid` | admin; đơn đã giao |
| `* -> refund_pending` | chỉ qua hủy/hoàn hàng (hệ thống) hoặc yêu cầu hoàn tiền được duyệt |
| `refund_pending -> refunded` | chỉ qua màn "Hoàn tiền" khi đã ghi có vào ví |
| mọi chuyển tay | ghi `OrderStatusLog` kèm người làm |

---

## 2. Hoàn tiền: khách yêu cầu, admin xử lý, hoàn về ví ToyLand

### 2.1 Các tình huống cần hoàn

| # | Tình huống | Hiện nay |
|---|---|---|
| A | Đơn đã trả tiền rồi bị hủy hoặc hoàn hàng | Hệ thống tự đặt `refund_pending`, nhưng không ai xử lý tiếp, không có giao diện |
| B | **MoMo đã trừ tiền khách nhưng shop không nhận được** (IPN lỗi, đơn đã hết hạn) | `paymentReceived` chuyển `refund_pending` nếu tiền về muộn. Nếu IPN không bao giờ về thì shop không biết |
| C | Khách trả tiền xong, đã nhận hàng, muốn trả hàng | Không có luồng |
| D | Thanh toán trùng (bấm "thanh toán lại" hai lần, cả hai đều thu tiền) | Không phát hiện |

Cả 4 tình huống dùng chung **một đối tượng "Yêu cầu hoàn tiền"** và **một nơi xử lý**, thay vì mỗi nơi tự đổi nhãn.

### 2.2 Dữ liệu mới

**`refund_requests`**: mỗi yêu cầu một dòng.

| Cột | Ý nghĩa |
|---|---|
| `order_id`, `user_id`, `payment_transaction_id` | Khoản tiền nào của đơn nào |
| `type` | `order_cancelled` (A), `payment_not_received` (B), `return_goods` (C), `duplicate_payment` (D) |
| `source` | `system` hoặc `customer` |
| `amount` | Số tiền xin hoàn (server tính lại, không tin số khách gửi) |
| `reason`, `customer_note`, `momo_trans_id` | Lý do, ghi chú, mã giao dịch MoMo khách cung cấp (tình huống B) |
| `status` | `requested -> approved -> refunded`, hoặc `rejected` |
| `destination` | `wallet` (mặc định). Chừa chỗ cho `momo` sau này |
| `handled_by`, `handled_at`, `admin_note` | Ai duyệt, lúc nào, lý do từ chối |
| Ràng buộc | duy nhất `(payment_transaction_id)` khi `status` còn mở hoặc đã `refunded`: một khoản tiền chỉ hoàn một lần |

**Ví ToyLand**: sổ cái chỉ ghi thêm, không sửa.

- `users.wallet_balance` (số nguyên, đồng): số dư hiện tại.
- `wallet_transactions`: `user_id`, `type` (`refund_in`, `order_payment`, `order_cancel_return`, `adjust`), `amount` (+/-), `balance_after`, `order_id`, `refund_request_id`, `note`, `created_by`. Mỗi lần đổi số dư đều ghi một dòng, trong một giao dịch CSDL có khóa dòng user (`lockForUpdate`) để hai thao tác song song không làm sai số dư.

Vì sao ví nội bộ mà không hoàn thẳng về MoMo: hoàn thẳng cần API refund của MoMo (cần `transId`, chữ ký, môi trường test hạn chế) và tiền mất 1-3 ngày. Ví nội bộ hoàn tức thì, khớp đúng nhu cầu bạn nêu là khách dùng lại cho đơn sau, và không phụ thuộc cổng thanh toán. Rút tiền ra MoMo/ngân hàng để làm pha sau nếu cần.

### 2.3 Giao diện khách hàng (chỗ khách yêu cầu hoàn tiền)

1. **Trang chi tiết đơn** (`orders/show`): thêm khối "Thanh toán":
   - Đơn đã trả tiền (`paid`) mà đơn `cancelled`/`returned`: hiển thị "Đang chờ shop hoàn tiền" (tình huống A, hệ thống đã tự tạo yêu cầu).
   - Đơn MoMo ở `awaiting_payment` hoặc `cancelled` mà khách thấy đã bị trừ tiền: nút **"Tôi đã bị trừ tiền nhưng đơn chưa ghi nhận"** (tình huống B). Form: mã giao dịch MoMo (bắt buộc), ghi chú. Gửi yêu cầu `payment_not_received`.
   - Đơn đã `completed` và còn trong hạn đổi trả (7 ngày, khớp chính sách hiện có): nút **"Yêu cầu trả hàng và hoàn tiền"** với lý do bắt buộc (tình huống C). Luồng này gắn với bước hoàn hàng của vận chuyển; tiền chỉ hoàn sau khi hàng về kho (`confirm_returned`).
   - Mỗi đơn chỉ được một yêu cầu đang mở. Có dòng theo dõi: Đã gửi, Shop đang xem, Đã hoàn vào ví hoặc Bị từ chối kèm lý do.
2. **Trang "Ví của tôi"** (mới, menu tài khoản): số dư, lịch sử cộng/trừ, danh sách yêu cầu hoàn tiền và trạng thái.
3. **Thanh toán bằng ví** (trang checkout): ô "Dùng số dư ví (đang có X đ)". Trừ ví trước, phần còn lại thanh toán COD hoặc MoMo. Nếu ví đủ cả đơn thì đặt đơn không cần cổng thanh toán.
   - Hủy đơn đã trừ ví: hoàn lại ví tự động ngay (tiền của chính ví, không cần duyệt).
4. Mọi bước đều có thông báo trong ứng dụng và email (đã có sẵn `UserNotification`, `PaymentStatusChangedMail`).

### 2.4 Giao diện admin (nơi xử lý hoàn tiền)

Menu mới **"Hoàn tiền"** (cạnh Tài chính):

- Tab: Chờ duyệt, Đã duyệt chờ hoàn, Đã hoàn, Từ chối. Có huy hiệu số yêu cầu chờ ở menu.
- Chi tiết một yêu cầu: thông tin đơn, trạng thái vận chuyển, giao dịch thanh toán (cổng, mã, thời điểm thu), số tiền, lý do và mã MoMo khách cung cấp. Với tình huống B, ghi chú nhắc admin đối chiếu mã giao dịch trong cổng quản trị MoMo trước khi duyệt.
- Hai nút:
  - **Duyệt, hoàn về ví**: một thao tác nguyên tử (xem 2.5).
  - **Từ chối**: bắt buộc nhập lý do, báo khách.
- Trang Tài chính chỉ còn *hiển thị* các khoản đang `refund_pending` và liên kết sang màn Hoàn tiền, không cho đổi tay.

### 2.5 Thao tác "Duyệt, hoàn về ví" (một giao dịch CSDL)

```
BEGIN
  khóa refund_request, đơn, giao dịch thanh toán, user (lockForUpdate)
  kiểm tra: status = requested/approved, chưa hoàn, amount <= đã thu - đã hoàn
  ghi wallet_transactions (+amount) và tăng users.wallet_balance
  payment_transactions.status = 'refunded'
  orders.payment_status = 'refunded'
  refund_requests.status = 'refunded', handled_by, handled_at
  ghi OrderStatusLog (ai, số tiền, lý do)
COMMIT
-- ngoài giao dịch: thông báo trong ứng dụng + email cho khách
```

Idempotent: bấm hai lần hoặc hai admin cùng bấm thì lần sau thấy `refunded` và dừng, không cộng ví hai lần.

### 2.6 Tình huống B chi tiết (MoMo trừ tiền, shop không nhận được)

1. Khách thấy bị trừ tiền, vào chi tiết đơn, bấm "Tôi đã bị trừ tiền", nhập mã giao dịch MoMo.
2. Hệ thống tạo yêu cầu `payment_not_received`, thông báo cho admin.
3. Admin đối chiếu trong cổng quản trị MoMo:
   - Có giao dịch thành công, đơn còn giữ được (chưa hết hạn): nút phụ "Ghi nhận đã thanh toán" gọi `OrderWorkflow::paymentReceived`, đơn đi tiếp, không cần hoàn.
   - Có giao dịch nhưng đơn đã hủy/hết hạn: duyệt hoàn về ví.
   - Không có giao dịch: từ chối kèm lý do.
4. Pha sau: tự truy vấn MoMo (API query theo `orderId`) để hệ thống tự đối chiếu, bỏ bước tay.

### 2.7 Quy tắc an toàn

- Số tiền hoàn tối đa = số đã thu trừ số đã hoàn của giao dịch đó; do server tính.
- Mỗi khoản thu chỉ một lần hoàn (ràng buộc duy nhất ở CSDL, không chỉ kiểm tra bằng mã).
- Đơn COD chưa thu (`pending`, `cod_collected`) không có gì để hoàn.
- Mọi thay đổi ví đi qua một lớp dịch vụ duy nhất (`WalletService`), không ai tự `increment` số dư.
- Ghi sổ đầy đủ: ai, khi nào, vì sao. Số dư luôn bằng tổng `wallet_transactions` (có lệnh kiểm tra chéo).
- Tài khoản bị xóa: ví không nên bị xóa theo (xem quyết định về khóa tài khoản trong tài liệu phân tích).

---

## 3. Mục 3 trong `tk.md`

Đang trống. Bạn gửi nội dung, mình bổ sung vào tài liệu này.

---

## 4. Kế hoạch triển khai

| Pha | Việc | Chạm vào |
|---|---|---|
| 1. Sửa luồng COD (nhỏ, nên làm ngay) | Thêm `cod_collected`; `collectCod` đặt trạng thái này; ô COD ở Tài chính chỉ cho `cod_collected -> paid/failed`; bỏ chọn tay `refund_pending/refunded`; ghi `OrderStatusLog`; chặn ở server | `FinanceController`, `OrderWorkflow::collectCod`, `Order::PAYMENT_LABELS`, view tài chính, email, test |
| 2. Ví + hoàn tiền | Migration `refund_requests`, `wallet_transactions`, cột `wallet_balance`; `WalletService`, `RefundService`; màn "Hoàn tiền" của admin; trang "Ví của tôi"; nút yêu cầu ở chi tiết đơn; hủy/hoàn hàng tự tạo yêu cầu (tình huống A) | Controller và view mới, `OrderWorkflow` (tự tạo yêu cầu khi hủy), thông báo, test |
| 3. Thanh toán bằng ví | Ô "Dùng số dư ví" ở checkout; trừ ví trong giao dịch đặt hàng; hủy đơn hoàn lại ví | `CheckoutController`, `OrderWorkflow::settlePaymentOnCancel`, test |
| 4. Tùy chọn | Tự truy vấn MoMo đối chiếu; rút ví về MoMo/ngân hàng; hạn mức hoàn tự duyệt | `MomoService` |

Phối hợp nhóm: Pha 1-2 chạm `OrderWorkflow` (TV4) và Tài chính/MoMo (TV3), nên một người làm hoặc chốt thứ tự để không xung đột. Cần thống nhất cách tính doanh thu khi có hoàn tiền (đơn `refunded` không tính doanh thu).

Kiểm thử cần có:

1. Đơn COD đã giao: `cod_collected` tự gán, admin không thể đặt `refund_pending` bằng ô cập nhật.
2. Duyệt hoàn hai lần liên tiếp: ví chỉ cộng một lần.
3. Hai admin duyệt cùng lúc: một thành công, một bị chặn.
4. Hoàn vượt số đã thu: bị từ chối.
5. Tiền MoMo về muộn sau khi đơn hết hạn: tự tạo yêu cầu, duyệt xong ví tăng đúng, đơn `refunded`.
6. Dùng ví thanh toán một phần rồi hủy đơn: ví được hoàn đúng phần đã trừ.
7. Số dư ví luôn bằng tổng sổ cái.

## 5. Điểm cần bạn quyết định

1. Đặt tên trạng thái mới: "Đã thu hộ - chờ đối soát" (đề xuất) hay chỉ "Chờ xác nhận".
2. Hoàn về **ví ToyLand** trước (đề xuất), rút ra MoMo để sau.
3. Khách có được dùng ví cho **một phần** đơn không (đề xuất: có, phần còn lại COD hoặc MoMo).
4. Yêu cầu trả hàng đã nhận (tình huống C) có làm trong đợt này không, hay chỉ làm A, B, D trước.
