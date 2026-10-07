# Luồng xử lý đơn hàng ToyLand

Mọi thay đổi trạng thái đơn đều đi qua **một chỗ duy nhất**: `App\Services\Orders\OrderWorkflow`. Tất cả nguồn thay đổi đều gọi vào đây:

- Shop bấm nút.
- Khách tự hủy.
- Bên vận chuyển báo kết quả.
- MoMo báo đã nhận tiền.
- Hệ thống tự hủy đơn quá hạn.

Nhờ vậy:

- Không ai nhảy cóc được giữa các bước.
- Mỗi vai trò chỉ làm đúng việc của mình.
- Các việc đi kèm (trả kho, thu tiền, tạo mã vận đơn, cộng điểm, ghi nhật ký, báo khách) không bao giờ bị sót.
- Mỗi đơn bị khóa dòng (`lockForUpdate`) trong lúc xử lý, nên thao tác đồng thời hay xử lý hàng loạt trùng đơn cũng không làm sai dữ liệu.

## 1. Sơ đồ trạng thái

```
            (MoMo)                                   (COD bắt đầu từ đây)
   ┌──────────────────────┐   MoMo báo đã trả tiền   ┌──────────────┐  shop xác nhận  ┌─────────────┐
   │ awaiting_payment     │ ───────────────────────▶ │ pending      │ ──────────────▶ │ confirmed   │
   │ Chờ thanh toán       │                          │ Chờ xác nhận │                 │ Đã xác nhận │
   │ (giữ hàng 30 phút)   │                          └──────┬───────┘                 └──────┬──────┘
   └──────────┬───────────┘                                 │                                │ shop đóng gói
              │ quá 30 phút / khách hủy                     │ shop/khách hủy                 ▼ + TẠO MÃ VẬN ĐƠN
              ▼                                             ▼                          ┌─────────────┐
        ┌───────────┐ ◀─────────────────────────────────────┴──────── shop hủy ─────── │ packed      │
        │ cancelled │                                                                  │ Đã đóng gói │
        │ Đã hủy    │                                                                  └──────┬──────┘
        └───────────┘                                                                         │ BÊN VẬN CHUYỂN lấy hàng
                                                                                              ▼
   ┌───────────┐   giao thành công    ┌──────────────────────────────────────────────────────────────┐
   │ completed │ ◀─────────────────── │ shipping - Đang vận chuyển                                   │
   │ Đã giao   │                      │   picked ─▶ delivering ─▶ delivery_fail ─▶ delivering (giao lại)│
   └───────────┘                      └──────────────────────┬───────────────────────────────────────┘
                                                             │ giao thất bại → chuyển hoàn
                                                             ▼
                                ┌────────────────┐  shop nhận lại hàng  ┌───────────────┐
                                │ returning      │ ───────────────────▶ │ returned      │
                                │ Đang hoàn hàng │                      │ Đã hoàn hàng  │
                                └────────────────┘                      └───────────────┘
```

Có **hai cột trạng thái riêng**:

- `status`: vòng đời đơn, là thứ khách và shop nhìn thấy.
- `shipping_status`: bước vận chuyển chi tiết, dùng chung bộ mã với GHN (`ready_to_pick`, `picked`, `delivering`, `delivery_fail`, `returning`, `returned`…).

Thanh toán nằm ở cột thứ ba, `payment_status`: `pending`, `paid`, `failed`, `cancelled`, `refund_pending`, `refunded`.

## 2. Ai được làm gì

| Hành động | Vai trò | Từ trạng thái | Sang | Việc đi kèm |
|---|---|---|---|---|
| Ghi nhận đã thanh toán | Hệ thống (MoMo IPN/callback) | awaiting_payment | pending | Bỏ hạn giữ hàng |
| Hủy do quá hạn | Hệ thống | awaiting_payment (đã quá hạn) | cancelled | Trả kho, đóng giao dịch |
| Xác nhận đơn | Shop | pending | confirmed | Đơn MoMo phải đã trả tiền |
| Đóng gói & tạo vận đơn | Shop | confirmed | packed | **Tạo mã vận đơn**, shipping = `ready_to_pick` |
| Hủy đơn | Shop, khách | awaiting_payment, pending, confirmed, (packed: chỉ shop) | cancelled | Trả kho, hủy vận đơn nếu có; đã trả tiền thì chuyển sang "Chờ hoàn tiền" |
| Lấy hàng | Bên vận chuyển | packed | shipping (`picked`) | Ghi thời điểm lấy hàng |
| Đi giao hàng | Bên vận chuyển | shipping (`picked`/`delivery_fail`) | shipping (`delivering`) | Tăng số lần giao (tối đa 3) |
| Giao thành công | Bên vận chuyển | shipping (`delivering`) | completed | COD: ghi đã thu hộ; cộng điểm thưởng |
| Giao thất bại | Bên vận chuyển | shipping (`delivering`) | shipping (`delivery_fail`) | Bắt buộc ghi lý do, báo khách và shop |
| Chuyển hoàn | Bên vận chuyển | shipping (`delivery_fail`) | returning | Bắt buộc ghi lý do |
| Đã nhận lại hàng hoàn | Shop hoặc bên vận chuyển | returning | returned | Trả kho; COD: hủy thu tiền; MoMo: "Chờ hoàn tiền" |

Khách chỉ tự hủy được khi **chưa thanh toán online** và bên vận chuyển **chưa lấy hàng**. Sau khi đã lấy hàng, không ai "hủy" được nữa: đơn chỉ có thể được giao thành công hoặc chuyển hoàn.

## 3. Đơn COD

1. Khách đặt hàng. Kho bị trừ ngay, đơn ở **Chờ xác nhận**, giao dịch COD ở `pending`.
2. Shop **Xác nhận**, rồi **Đóng gói & tạo vận đơn**. Mã vận đơn sinh ra ở bước này, và phiếu giao hàng in được.
3. Bên vận chuyển **Lấy hàng**, **Đi giao**, rồi báo kết quả:
   - **Giao thành công** → đơn *Đã giao*, tiền COD chuyển *Đã thanh toán* (thu hộ), khách được cộng điểm.
   - **Giao thất bại** (ghi lý do) → giao lại, tối đa 3 lần. Sau đó **Chuyển hoàn** → shop **Đã nhận lại hàng hoàn** → kho được cộng lại, thanh toán COD chuyển *Đã hủy*.

## 4. Đơn MoMo

1. Khách đặt hàng. Kho bị trừ ngay để **giữ hàng**, đơn ở **Chờ thanh toán** với hạn `payment_expires_at` = sau 30 phút.
2. Khách được chuyển sang trang MoMo. Kết quả về theo hai đường:
   - **callback**: trình duyệt của khách quay lại web.
   - **IPN**: máy chủ MoMo gọi thẳng vào web; đường này vẫn chạy kể cả khi khách đóng trình duyệt.

   Hai đường dùng chung một hàm xử lý và không bị ghi nhận hai lần.
3. Đã trả tiền → đơn sang **Chờ xác nhận**, sau đó đi tiếp như COD nhưng không thu hộ khi giao.
4. Thanh toán thất bại → đơn vẫn **Chờ thanh toán**; khách bấm "Thanh toán lại" được cho tới khi hết hạn.
5. Quá 30 phút chưa trả tiền → đơn **tự hủy và trả kho**. Không có cron chạy từng phút trên Render gói miễn phí, nên việc dọn đơn quá hạn chạy ở các thời điểm sau:
   - Lúc có người đặt hàng (trước khi kiểm tra tồn kho).
   - Lúc mở danh sách đơn.
   - Lúc khách xem đơn.
   - Lúc gọi `/api/cron/daily`.
   - Mỗi phút, nếu máy chủ có cron (`orders:release-expired`).
6. Tiền về **sau khi** đơn đã hủy → đơn **không** được hồi sinh. Thanh toán chuyển *Chờ hoàn tiền*, hệ thống báo cho shop và khách.
7. Shop hủy đơn đã trả tiền, hoặc hàng bị hoàn về → *Chờ hoàn tiền*. Shop xử lý hoàn tiền ở trang **Tài chính**.

## 5. Mã vận đơn từ đâu ra

Mã vận đơn được tạo đúng lúc shop bấm **"Đóng gói & tạo vận đơn"**: khi đó hàng đã đóng xong, cân được, dán nhãn được, nên mới gọi đơn vị vận chuyển tới lấy. Nguồn cấp mã phụ thuộc biến `SHIPPING_CARRIER`:

- `simulated` (mặc định): ToyLand Express, bản mô phỏng.
  - Hệ thống tự cấp mã dạng `TL` + `yymmdd` + mã đơn 6 chữ số, ví dụ `TL261001000123`.
  - Mã gắn với mã đơn nên không bao giờ trùng.
  - Quản trị viên **đóng vai bên giao hàng** ở màn hình **Đơn vị vận chuyển**.
- `ghn`: gọi API Giao Hàng Nhanh (`/v2/shipping-order/create`), mã do GHN cấp.
  - Hủy đơn khi đã đóng gói sẽ gọi API hủy vận đơn của GHN.
  - Trạng thái giao nhận về sau nên nhận qua webhook GHN (chưa làm).

Mã được lưu ở cột `orders.ghn_order_code`; cột `orders.carrier` ghi đơn vị nào đã cấp mã.

## 6. Màn hình và xử lý hàng loạt

- **Quản trị → Đơn hàng**
  - Các tab theo đúng hàng chờ việc: Chờ thanh toán, Chờ xác nhận, Chờ đóng gói, Chờ lấy hàng, Đang giao, Thành công, Hoàn hàng, Đã hủy.
  - Tick chọn nhiều đơn rồi chọn một thao tác: **Xác nhận**, **Đóng gói & tạo vận đơn**, **Hủy** (bắt buộc ghi lý do) hoặc **In phiếu giao hàng**.
  - Mỗi lần tối đa 100 đơn. Đơn nào sai bước thì bị bỏ qua và báo rõ lý do; các đơn khác vẫn được xử lý.
- **Quản trị → Đơn vị vận chuyển**
  - Các tab: Chờ lấy hàng, Đang giao, Đang hoàn, Đã xong.
  - Tìm theo mã vận đơn, mã đơn hoặc số điện thoại.
  - Mỗi đơn chỉ hiện đúng những nút hợp lệ ở bước hiện tại.
  - Thao tác hàng loạt: lấy hàng cả chuyến, đi giao cả tuyến, giao thành công, nhận hàng hoàn. Riêng giao thất bại và chuyển hoàn phải làm từng đơn vì cần ghi lý do.
  - Góc trên hiện tổng tiền COD đang cầm.
- **Phiếu giao hàng**: mỗi phiếu gồm mã vận đơn, người nhận, địa chỉ, danh sách món và **số tiền thu hộ**. In thẳng từ trình duyệt.
- **Khách → Đơn của tôi**:
  - Dòng thời gian có bước Chờ thanh toán (với đơn MoMo) và nhánh Hoàn hàng.
  - Hiện hạn giữ hàng, mã vận đơn, trạng thái vận chuyển và số lần giao.

## 7. Kịch bản kiểm thử bằng tay (đóng vai bên giao hàng)

1. Đăng nhập tài khoản khách, đặt **2 đơn COD** và **1 đơn MoMo**.
   - Đơn MoMo thanh toán bằng thẻ test ATM `9704 0000 0000 0018`.
2. Đăng nhập admin → **Đơn hàng** → tab *Chờ xác nhận* → tick cả 3 đơn → **Xác nhận đơn**.
3. Tab *Chờ đóng gói* → tick → **Đóng gói & tạo vận đơn** → **In phiếu giao hàng**.
4. **Đơn vị vận chuyển** → tick các đơn → **Đã lấy hàng**, rồi **Đi giao hàng**.
5. Đơn thứ nhất: **Giao thành công**. Kiểm tra: đơn COD tự chuyển *Đã thanh toán*, khách được cộng điểm.
6. Đơn thứ hai: **Giao thất bại** (ghi "Khách không nghe máy") → **Đi giao hàng** lại → thất bại tiếp → **Chuyển hoàn** → **Đã nhận lại hàng hoàn**. Kiểm tra: tồn kho được cộng lại.
7. Đặt thêm 1 đơn MoMo nhưng **không thanh toán**. Sau 30 phút, mở *Đơn của tôi* hoặc danh sách đơn của admin: đơn tự hủy và kho được trả lại.
8. Mỗi bước, mở lại trang đơn của khách để xem dòng thời gian, chuông thông báo và email.

Kiểm thử tự động:

- **42 phép thử nghiệp vụ** trên CSDL thật, gồm: COD giao thành công; giao thất bại 3 lần rồi hoàn hàng và trả kho; MoMo trả tiền rồi hủy thì sang chờ hoàn tiền; MoMo quá hạn tự hủy; tiền về muộn; khách hủy; phân quyền theo vai trò; xử lý hàng loạt có đơn sai bước; chống trả kho hai lần.
- **Kiểm thử qua giao diện**: đặt hàng, xử lý hàng loạt, in phiếu, màn hình vận chuyển, trang của khách.
