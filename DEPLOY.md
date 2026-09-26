# Triển khai ToyLand: GitHub → Render + Aiven MySQL + Cloudinary

Làm lần lượt từ trên xuống. Chỗ nào ghi **[bí mật]** thì chỉ dán vào Render / file `.env` trên máy, không dán vào file nào khác trong dự án.

---

## 0. Việc phải làm ngay: thu hồi khóa OpenAI cũ

File `.env.example` cũ có một khóa OpenAI thật (`sk-proj-...`). File đã được làm sạch, nhưng khóa đó coi như đã lộ:

1. Vào https://platform.openai.com/api-keys và bấm **Revoke** khóa đó.
2. Nếu vẫn cần dùng, tạo khóa mới và chỉ đặt nó trong `.env` trên máy.

---

## 1. Chuyển máy nhà (Laragon) sang MySQL

1. Laragon → **Start All** (MySQL chạy cổng 3306, user `root`, mật khẩu trống).
2. Laragon → **Database** (HeidiSQL) → tạo database `toyland`, collation `utf8mb4_unicode_ci`.
3. Sửa `.env` trên máy (tự sửa tay, file này không bao giờ lên GitHub):
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=toyland
   DB_USERNAME=root
   DB_PASSWORD=
   ```
4. Mở Terminal của Laragon trong thư mục dự án rồi chạy:
   ```
   php artisan config:clear
   php artisan migrate:fresh --seed
   ```
   Lệnh này tạo lại toàn bộ bảng và nạp dữ liệu mẫu. Tài khoản mẫu trên máy: `admin@tpcn.com / Admin@123`.
5. Nếu PHP của Laragon chưa bật `pdo_mysql`: Menu → PHP → Extensions → tick `pdo_mysql`.

---

## 2. Đưa mã nguồn lên GitHub (repo **Private**)

1. Trên GitHub: **New repository** → tên `toyland` → chọn **Private** → không tick README / .gitignore → **Create**.
2. Trong thư mục dự án (Terminal Laragon hoặc Git Bash):
   ```
   git init
   git branch -M main
   git add .
   git status
   ```
   **Kiểm tra `git status` trước khi commit.** Danh sách **không được** có: `.env`, `tk.md`, `1.md`, `*.docx`, `vendor/`, `.claude/`, `Claude outputs/`. Nếu thấy một trong số đó, dừng lại và kiểm tra `.gitignore`.
3. Commit và đẩy lên:
   ```
   git commit -m "ToyLand: ban dau"
   git remote add origin https://github.com/<ten-ban>/toyland.git
   git push -u origin main
   ```
   Khi được hỏi mật khẩu, dùng **Personal Access Token** của GitHub (Settings → Developer settings → Tokens), không dùng mật khẩu đăng nhập.
4. **Các lần cập nhật sau:**
   ```
   git add .
   git commit -m "Mo ta ngan thay doi"
   git push
   ```
   Render tự build lại sau mỗi lần `push` lên nhánh `main`.

---

## 3. Aiven: tạo MySQL miễn phí

1. https://console.aiven.io → **Create service** → **MySQL** → gói **Free**.
2. Chọn vùng gần Việt Nam nhất có trong danh sách, rồi đặt tên và bấm **Create**. Chờ trạng thái **Running**.
3. Trong tab **Overview → Connection information**, ghi lại:
   - **Host** → `DB_HOST`
   - **Port** → `DB_PORT` (thường là số 5 chữ số, không phải 3306)
   - **User** → `avnadmin`
   - **Password** → `DB_PASSWORD` **[bí mật]**
   - **Database** → `defaultdb`
4. Bấm **CA certificate → Download** để lấy file `ca.pem`. Giữ file này trên máy, không bỏ vào thư mục dự án.

> Gói miễn phí: 1 GB RAM, 1 GB ổ đĩa, tối đa 76 kết nối. Aiven có thể tắt dịch vụ nếu lâu không dùng (có email báo trước). Nếu web báo lỗi CSDL, vào Aiven xem dịch vụ còn **Running** không và bấm **Power on** nếu cần.

---

## 4. Cloudinary: nơi lưu ảnh

1. https://console.cloudinary.com → **Dashboard** (hoặc Settings → API Keys).
2. Sao chép chuỗi **API environment variable** dạng `cloudinary://<api_key>:<api_secret>@<cloud_name>` → đây là `CLOUDINARY_URL` **[bí mật]**.

Ảnh sản phẩm lưu vào thư mục `toyland/products`, ảnh bài viết vào `toyland/posts`. Ảnh lớn hơn 1600px được tự thu nhỏ khi tải lên để tiết kiệm dung lượng gói miễn phí.

---

## 5. Brevo: gửi email

Render gói miễn phí **chặn** các cổng gửi thư 25, 465 và 587, nên Gmail SMTP không dùng được trên Render. Brevo cho gửi qua cổng **2525** (miễn phí 300 thư/ngày):

1. Đăng ký https://www.brevo.com → **Senders** → thêm và xác minh email người gửi → đây là `MAIL_FROM_ADDRESS`.
2. **SMTP & API → SMTP → Generate a new SMTP key**. Ghi lại:
   - **Login** → `MAIL_USERNAME`
   - **SMTP key** → `MAIL_PASSWORD` **[bí mật]**

Không cấu hình email thì web vẫn chạy, nhưng khách đăng ký mới sẽ không nhận được link xác thực.

---

## 6. Render: chạy web

1. https://dashboard.render.com → **New → Blueprint** → kết nối GitHub → chọn repo `toyland`. Render đọc `render.yaml` và hỏi các biến bí mật:

   | Biến | Giá trị |
   |---|---|
   | `APP_KEY` | Chạy `php artisan key:generate --show` trên máy rồi dán cả chuỗi `base64:...` |
   | `APP_URL` | Tạm để `https://toyland.onrender.com`, sửa lại sau khi Render cấp địa chỉ thật |
   | `DB_HOST`, `DB_PORT`, `DB_PASSWORD` | Lấy ở bước 3 |
   | `CLOUDINARY_URL` | Lấy ở bước 4 |
   | `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | Lấy ở bước 5 |
   | `ADMIN_EMAIL`, `ADMIN_PASSWORD` | Email và mật khẩu **mạnh** cho tài khoản quản trị trên web thật |

2. Sau khi dịch vụ được tạo, mở **Environment → Secret Files → Add Secret File**:
   - Filename: `ca.pem`
   - Contents: mở file `ca.pem` của Aiven bằng Notepad, dán toàn bộ nội dung vào

   Sau đó bấm **Save** và **Manual Deploy → Deploy latest commit**.
3. Xem tab **Logs**. Lần đầu sẽ thấy lần lượt: `Migrating...`, `CSDL còn trống -> nạp dữ liệu mẫu...`, rồi `Sẵn sàng`.
4. Lấy địa chỉ thật (dạng `https://toyland-xxxx.onrender.com`), sửa lại `APP_URL` cho khớp, rồi deploy lại.

> Gói miễn phí sẽ ngủ sau 15 phút không có ai truy cập, và lần vào đầu tiên sau đó mất khoảng 1 phút để khởi động. Khi đi thuyết trình, hãy mở web trước vài phút.

---

## 7. cron-job.org: việc hằng ngày (nhắc sinh nhật bé)

Render gói miễn phí không có cron. Thay vào đó, dùng một dịch vụ ngoài gọi vào web mỗi sáng:

1. Trên Render, mở **Environment** → xem giá trị `CRON_TOKEN` (Render đã tự sinh) → sao chép.
2. https://cron-job.org → **Create cronjob**:
   - URL: `https://<dia-chi-render>/api/cron/daily`
   - Lịch: mỗi ngày lúc 08:00, múi giờ Asia/Ho_Chi_Minh
   - Tab **Advanced → Headers**: thêm `Authorization` = `Bearer <CRON_TOKEN>`
   - Timeout: đặt mức tối đa (máy chủ có thể đang ngủ)
3. Nên tạo thêm một job giống hệt lúc **08:05**. Nếu lần 08:00 hết thời gian chờ vì máy chủ đang ngủ dậy, lần 08:05 sẽ chạy bù. Mỗi việc chỉ thực hiện **một lần mỗi ngày** dù bị gọi bao nhiêu lần.

Kiểm tra: gọi URL kèm header sẽ trả JSON dạng `{"date":"...","tasks":{"toyland:birthday-reminder":"xong"}}`.

---

## 8. MoMo sandbox trên web thật (nếu dùng)

Thêm vào Environment trên Render:
```
MOMO_PARTNER_CODE / MOMO_ACCESS_KEY / MOMO_SECRET_KEY   [bí mật]
MOMO_REDIRECT_URL = https://<dia-chi-render>/payment/momo/callback
MOMO_IPN_URL      = https://<dia-chi-render>/payment/momo/ipn
```
Khi chạy trên máy nhà, MoMo không gọi được IPN vào `localhost`. Trên Render thì gọi được, nên đơn MoMo sẽ tự chuyển sang "đã thanh toán" kể cả khi khách đóng trình duyệt trước lúc quay về web.

---

## 9. Xử lý sự cố nhanh

| Triệu chứng trong Logs | Nguyên nhân / cách sửa |
|---|---|
| `không đọc được file CA` | Chưa thêm Secret File hoặc đặt sai tên (phải đúng là `ca.pem`) |
| `SQLSTATE[HY000] [2002]` / không migrate được | Sai `DB_HOST`/`DB_PORT`, hoặc dịch vụ Aiven đang tắt |
| `SQLSTATE[HY000] [1045] Access denied` | Sai `DB_USERNAME`/`DB_PASSWORD` |
| `chưa khai APP_KEY` | Thiếu `APP_KEY` |
| `Bỏ qua tạo admin: chưa khai ADMIN_PASSWORD` | Thêm `ADMIN_PASSWORD` rồi deploy lại; hệ thống sẽ tự tạo admin |
| Tải ảnh báo "Máy chủ lưu ảnh từ chối" | Sai `CLOUDINARY_URL` |
| Đăng ký báo "chưa gửi được email xác thực" | Kiểm tra cấu hình Brevo; khách chỉ cần đăng nhập lại sau vài phút, hệ thống sẽ tự gửi lại link |
| Trang lỗi 500 không rõ nguyên nhân | Xem tab Logs (lỗi được ghi ra `stderr`). **Không** bật `APP_DEBUG=true` trên web thật |

## Kiểm thử nhanh sau khi deploy

Từ máy có Python 3 và thư viện `requests`, chạy:
```
SMOKE_ADMIN_EMAIL=<admin> SMOKE_ADMIN_PASSWORD=<mat-khau> python tools/smoke_test.py https://<dia-chi-render>
```
Lưu ý: script này **tạo đơn hàng, đánh giá, ticket thật**, nên chỉ chạy trên bản thử nghiệm.
