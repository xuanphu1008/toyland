#!/usr/bin/env python3
"""
KIỂM THỬ KHÓI (smoke test) TOÀN HỆ THỐNG TOYLAND

Đóng vai ba người dùng thật và bấm qua mọi trang:
  1. Khách vãng lai  - xem mọi trang công khai
  2. Khách hàng      - thêm giỏ, đặt hàng COD, khai hồ sơ bé, gửi hỗ trợ,
                       yêu thích, so sánh, rồi đánh giá sau khi đơn giao xong
  3. Quản trị viên   - mở mọi trang quản trị, đẩy đơn qua đủ các trạng thái,
                       cập nhật thanh toán COD, xuất file CSV

Bất kỳ trang nào trả về lỗi 5xx đều bị ghi lại. Chạy được với cả máy cục bộ
lẫn bản đã deploy:

    python3 tools/smoke_test.py http://127.0.0.1:8000
    python3 tools/smoke_test.py https://toyland.onrender.com

Tài khoản đăng nhập lấy từ biến môi trường SMOKE_ADMIN_EMAIL /
SMOKE_ADMIN_PASSWORD / SMOKE_USER_EMAIL / SMOKE_USER_PASSWORD - không ghi
cứng mật khẩu production vào mã nguồn.
"""
import os, re, sys, time
import requests

BASE = (sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8000").rstrip("/")
ADMIN = (os.environ.get("SMOKE_ADMIN_EMAIL", "admin@tpcn.com"), os.environ.get("SMOKE_ADMIN_PASSWORD", "Admin@123"))
USER = (os.environ.get("SMOKE_USER_EMAIL", "khachhang1@tpcn.com"), os.environ.get("SMOKE_USER_PASSWORD", "Khach@123"))

results = []      # (vai, phương thức, đường dẫn, mã, thời gian)
failures = []


def token(html):
    m = re.search(r'name="csrf-token" content="([^"]+)"', html) or re.search(r'name="_token" value="([^"]+)"', html)
    return m.group(1) if m else None


def hit(s, role, method, path, data=None, expect_ok=True, allow=(200, 302)):
    url = path if path.startswith("http") else BASE + path
    t = time.time()
    try:
        r = s.request(method, url, data=data, allow_redirects=False, timeout=60)
        code = r.status_code
    except requests.RequestException as e:
        code, r = 0, None
        print(f"   ✘ {role:6} {method:6} {path}  KHÔNG KẾT NỐI ĐƯỢC: {e}")
    dt = time.time() - t
    results.append((role, method, path, code, dt))
    bad = code == 0 or code >= 500 or (expect_ok and code not in allow)
    if bad:
        failures.append((role, method, path, code))
        print(f"   ✘ {role:6} {method:6} {path}  -> {code}")
    return r


def login(s, email, password):
    r = s.get(BASE + "/login", timeout=60)
    r = s.post(BASE + "/login", data={"_token": token(r.text), "email": email, "password": password},
               allow_redirects=False, timeout=60)
    ok = r.status_code == 302 and "/login" not in r.headers.get("Location", "")
    print(f"   {'✔' if ok else '✘'} đăng nhập {email}")
    if not ok:
        failures.append(("login", "POST", email, r.status_code))
    return ok


def csrf(s, path="/"):
    return token(s.get(BASE + path, timeout=60).text)


def ids_from(html, pattern):
    return list(dict.fromkeys(re.findall(pattern, html)))


# =====================================================================
print(f"\n=== KIỂM THỬ {BASE} ===\n")

# ---------------- 1. KHÁCH VÃNG LAI ----------------
print("1. Khách vãng lai")
g = requests.Session()
home = hit(g, "khách", "GET", "/")
product_ids = ids_from(home.text if home is not None else "", r'/san-pham/(\d+)')[:3]
for p in ["/", "/login", "/register", "/faq", "/tin-tuc", "/so-sanh", "/password/forgot"]:
    hit(g, "khách", "GET", p, expect_ok=False)
for pid in product_ids:
    hit(g, "khách", "GET", f"/san-pham/{pid}")
# Tìm kiếm và bộ lọc - nơi SQL viết tay dày đặc nhất
for q in ["?q=lego", "?q=b%C3%BAp+b%C3%AA", "?sort=price_asc", "?sort=bestseller", "?min_price=100000&max_price=500000", "?age=36", "?gender=girl"]:
    hit(g, "khách", "GET", "/" + q, expect_ok=False)
# Trợ lý tự động
t = csrf(g)
for msg in ["xin chào", "phí ship bao nhiêu", "tìm quà cho bé gái 5 tuổi", "1+1=?"]:
    hit(g, "khách", "POST", "/chatbot/gui", data={"_token": t, "message": msg}, expect_ok=False)
# Trang yêu cầu đăng nhập phải chuyển hướng, không được 500
for p in ["/cart", "/orders", "/admin", "/admin/finance"]:
    hit(g, "khách", "GET", p, expect_ok=False)

# ---------------- 2. KHÁCH HÀNG ----------------
print("2. Khách hàng")
u = requests.Session()
login(u, *USER)
for p in ["/", "/cart", "/orders", "/tai-khoan", "/yeu-thich", "/thong-bao", "/be-yeu", "/be-yeu/them",
          "/be-yeu/sinh-nhat", "/ho-tro", "/ho-tro/tao", "/danh-gia-cua-toi", "/ma-giam-gia"]:
    hit(u, "khách", "GET", p, expect_ok=False)

t = csrf(u)
for pid in product_ids[:2]:
    hit(u, "khách", "POST", f"/cart/add/{pid}", data={"_token": t, "quantity": 1})
    hit(u, "khách", "POST", f"/yeu-thich/{pid}", data={"_token": t}, expect_ok=False)
    hit(u, "khách", "POST", f"/so-sanh/{pid}", data={"_token": t}, expect_ok=False)
hit(u, "khách", "GET", "/so-sanh")
hit(u, "khách", "GET", "/checkout")

# Đặt hàng COD - đường đi quan trọng nhất của cả hệ thống
r = hit(u, "khách", "POST", "/checkout", data={
    "_token": t, "new_name": "Khách Kiểm Thử", "new_phone": "0912345678",
    "new_address": "12 Đường Thử, Phường Thử", "new_province_id": 201, "new_province_name": "Hà Nội",
    "new_district_name": "Ba Đình", "new_ward_name": "Phúc Xá",
    "to_district_id": 1484, "to_ward_code": "1A0101", "ghn_total_fee": 30000, "payment_method": "cod"})
orders_html = u.get(BASE + "/orders", timeout=60).text
order_ids = ids_from(orders_html, r'/orders/(\d+)')
print(f"   {'✔' if order_ids else '✘'} đơn hàng vừa tạo: {order_ids[:3]}")
if not order_ids:
    failures.append(("khách", "POST", "/checkout", "không tạo được đơn"))
for oid in order_ids[:1]:
    hit(u, "khách", "GET", f"/orders/{oid}")
    hit(u, "khách", "GET", f"/orders/{oid}/hoa-don")

hit(u, "khách", "POST", "/be-yeu", data={"_token": t, "name": "Bé Na", "birthday": "2021-03-15", "gender": "girl"})
hit(u, "khách", "POST", "/ho-tro", data={"_token": t, "subject": "Hỏi thời gian giao", "category": "shipping",
                                        "priority": "normal", "content": "Bao lâu thì nhận được hàng?"})
tickets = ids_from(u.get(BASE + "/ho-tro", timeout=60).text, r'/ho-tro/(\d+)')
for tid in tickets[:1]:
    hit(u, "khách", "GET", f"/ho-tro/{tid}")
children = ids_from(u.get(BASE + "/be-yeu", timeout=60).text, r'/be-yeu/(\d+)/sua')
for cid in children[:1]:
    hit(u, "khách", "GET", f"/be-yeu/{cid}/sua")
notes = ids_from(u.get(BASE + "/thong-bao", timeout=60).text, r'/thong-bao/(\d+)')
for nid in notes[:1]:
    hit(u, "khách", "GET", f"/thong-bao/{nid}", expect_ok=False)

# ---------------- 3. QUẢN TRỊ VIÊN ----------------
print("3. Quản trị viên")
a = requests.Session()
login(a, *ADMIN)
admin_pages = ["/admin", "/admin/products", "/admin/products/create", "/admin/categories", "/admin/categories/create",
               "/admin/orders", "/admin/users", "/admin/users/create", "/admin/reviews", "/admin/coupons",
               "/admin/coupons/create", "/admin/faqs", "/admin/faqs/create", "/admin/tickets", "/admin/pages",
               "/admin/pages/create", "/admin/posts", "/admin/posts/create", "/admin/reports", "/admin/reports/charts",
               "/admin/xuat-du-lieu", "/admin/so-sinh-nhat", "/admin/chatbot", "/admin/finance",
               "/admin/finance/transactions", "/admin/chat/users",
               "/admin/orders?tab=pending", "/admin/orders?sort=amount_desc&gateway=cod",
               "/admin/finance?min_amount=0&max_amount=99999999", "/admin/finance/transactions?sort=amount_desc&payment_status=pending"]
for p in admin_pages:
    hit(a, "admin", "GET", p)
for p in ["/admin/xuat-du-lieu/don-hang", "/admin/xuat-du-lieu/doanh-thu", "/admin/xuat-du-lieu/ton-kho",
          "/admin/xuat-du-lieu/khach-hang", "/admin/finance/export", "/admin/finance/export?gateway=cod&sort=amount_desc"]:
    r = hit(a, "admin", "GET", p)
    if r is not None and r.status_code == 200 and not r.content.startswith(b"\xef\xbb\xbf"):
        failures.append(("admin", "GET", p, "tệp CSV thiếu BOM UTF-8"))

# Trang chi tiết có tham số
listing = {
    "/admin/products": r'/admin/products/(\d+)/edit', "/admin/categories": r'/admin/categories/(\d+)/edit',
    "/admin/users": r'/admin/users/(\d+)', "/admin/coupons": r'/admin/coupons/(\d+)/edit',
    "/admin/faqs": r'/admin/faqs/(\d+)/edit', "/admin/pages": r'/admin/pages/(\d+)/edit',
    "/admin/posts": r'/admin/posts/(\d+)/edit', "/admin/tickets": r'/admin/tickets/(\d+)',
    "/admin/chatbot": r'/admin/chatbot/hoi-thoai/(\d+)',
}
for page, pattern in listing.items():
    found = ids_from(a.get(BASE + page, timeout=60).text, pattern)[:1]
    for i in found:
        path = re.sub(r'\(\\d\+\)', i, pattern).replace("\\", "")
        hit(a, "admin", "GET", path)
for pid in product_ids[:1]:
    hit(a, "admin", "GET", f"/admin/products/{pid}")

# Đẩy đơn qua đủ vòng đời rồi xác nhận thu tiền COD
for oid in order_ids[:1]:
    hit(a, "admin", "GET", f"/admin/orders/{oid}")
    hit(a, "admin", "GET", f"/admin/orders/{oid}/hoa-don")
    at = csrf(a, f"/admin/orders/{oid}")
    for st in ["confirmed", "packed", "shipping", "completed"]:
        hit(a, "admin", "POST", f"/admin/orders/{oid}/status", data={"_token": at, "_method": "PATCH", "status": st})
    fin = a.get(BASE + "/admin/finance/transactions", timeout=60).text
    m = re.search(rf'/admin/finance/{oid}/status".*?name="current_payment_status" value="([^"]*)".*?'
                  rf'name="current_order_status" value="([^"]*)".*?name="current_payment_id" value="([^"]*)"', fin, re.S)
    if m:
        hit(a, "admin", "POST", f"/admin/finance/{oid}/status", data={
            "_token": at, "_method": "PATCH", "payment_status": "refund_pending",
            "current_payment_status": m.group(1), "current_order_status": m.group(2), "current_payment_id": m.group(3)})
    else:
        print("   ・ đơn đã giao COD đã tự ghi 'đã thu' - không còn ô cập nhật (đúng thiết kế)")

# Khách đánh giá sau khi đơn đã giao
for pid in product_ids[:1]:
    t = csrf(u, f"/san-pham/{pid}")
    hit(u, "khách", "POST", f"/san-pham/{pid}/danh-gia", data={"_token": t, "rating": 5, "comment": "Bé rất thích!"})
hit(u, "khách", "GET", "/danh-gia-cua-toi")
hit(a, "admin", "GET", "/admin/reviews")
hit(a, "admin", "GET", "/admin/reports")
hit(a, "admin", "GET", "/admin/reports/charts")

# =====================================================================
n = len(results)
slow = sorted(results, key=lambda x: -x[4])[:3]
print(f"\nĐã gọi {n} yêu cầu. Chậm nhất: " + ", ".join(f"{p} {dt:.2f}s" for _, _, p, _, dt in slow))
if failures:
    print(f"❌ {len(failures)} LỖI:")
    for f in failures:
        print("   ", f)
    sys.exit(1)
print("✅ KHÔNG CÓ LỖI NÀO")
