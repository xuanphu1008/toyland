#!/bin/sh
# =====================================================================
#  Chạy MỖI LẦN container khởi động (kể cả khi Render đánh thức máy ngủ).
#  Mọi bước ở đây đều an toàn khi chạy lặp lại.
# =====================================================================
set -e
cd /var/www/html

echo "[toyland] Khởi động ($(date '+%Y-%m-%d %H:%M:%S'))"

# --- Chứng chỉ CA của Aiven -----------------------------------------
# Cách 1 (khuyên dùng trên Render): Secret File "ca.pem" -> /etc/secrets/ca.pem
#          và đặt MYSQL_ATTR_SSL_CA=/etc/secrets/ca.pem
# Cách 2 (tiện khi chạy docker trên máy): dán nguyên nội dung PEM vào DB_CA_CERT
# Cách 2 chỉ được dùng khi cách 1 không có file (hoặc không khai đường dẫn).
if [ -n "$DB_CA_CERT" ] && { [ -z "$MYSQL_ATTR_SSL_CA" ] || [ ! -r "$MYSQL_ATTR_SSL_CA" ]; }; then
    printf '%s\n' "$DB_CA_CERT" > /tmp/db-ca.pem
    export MYSQL_ATTR_SSL_CA=/tmp/db-ca.pem
    echo "[toyland] Dùng chứng chỉ CA từ biến DB_CA_CERT."
fi

if [ -n "$MYSQL_ATTR_SSL_CA" ] && [ ! -r "$MYSQL_ATTR_SSL_CA" ]; then
    echo "[toyland] LỖI: không đọc được file CA tại $MYSQL_ATTR_SSL_CA" >&2
    # In ra TÊN các file bí mật đang có (không in nội dung) để thấy ngay đặt sai tên hay chưa thêm
    if [ -d /etc/secrets ] && [ -n "$(ls -A /etc/secrets 2>/dev/null)" ]; then
        echo "[toyland]      Các Secret File đang có trong /etc/secrets: $(ls -A /etc/secrets | tr '\n' ' ')" >&2
        echo "[toyland]      -> Đổi tên Secret File thành đúng 'ca.pem'." >&2
    else
        echo "[toyland]      Chưa có Secret File nào. Vào service toyland -> Environment -> Secret Files" >&2
        echo "[toyland]      -> Add Secret File, Filename: ca.pem, dán nội dung file CA của Aiven." >&2
    fi
    exit 1
fi

if [ -n "$MYSQL_ATTR_SSL_CA" ] && ! grep -q -- "-----BEGIN CERTIFICATE-----" "$MYSQL_ATTR_SSL_CA"; then
    echo "[toyland] LỖI: $MYSQL_ATTR_SSL_CA không phải chứng chỉ PEM (thiếu dòng -----BEGIN CERTIFICATE-----)." >&2
    echo "[toyland]      Mở file ca.pem tải từ Aiven bằng Notepad, chép TOÀN BỘ nội dung dán lại vào Secret File." >&2
    exit 1
fi

# Secret File của Render chỉ root đọc được. Script này chạy bằng root nên đọc
# được, nhưng Apache phục vụ web bằng user www-data -> mọi truy vấn CSDL từ
# trang web báo "failed loading cafile stream". Chép ra một bản www-data đọc
# được (nằm ngoài thư mục public/ nên không ai tải về qua web được).
if [ -n "$MYSQL_ATTR_SSL_CA" ]; then
    install -m 0644 "$MYSQL_ATTR_SSL_CA" /var/www/db-ca.pem
    export MYSQL_ATTR_SSL_CA=/var/www/db-ca.pem
fi

if [ -z "$APP_KEY" ]; then
    echo "[toyland] LỖI: chưa khai APP_KEY. Tạo bằng: php artisan key:generate --show" >&2
    exit 1
fi

# --- Bộ nhớ đệm cấu hình / route / view -----------------------------
# Phải chạy lúc khởi động (không phải lúc build) vì biến môi trường
# của Render chỉ có ở thời điểm này.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# --- Cập nhật cấu trúc CSDL -----------------------------------------
# Aiven gói miễn phí có thể mất vài giây để nhận kết nối -> chỉ THỬ LẠI khi là
# lỗi KẾT NỐI. Lỗi khác (sai cú pháp SQL, vi phạm ràng buộc...) thử lại cũng vô
# ích nên dừng ngay và in đúng thông báo lỗi.
attempt=1
while :; do
    if out=$(php artisan migrate --force 2>&1); then
        echo "$out"
        break
    fi

    if ! echo "$out" | grep -qE '\[(2002|2003|2005|2006|2013)\]|Connection refused|timed out|getaddrinfo|php_network_getaddresses|server has gone away'; then
        echo "$out" | grep -vE '^#[0-9]+ ' >&2
        echo "[toyland] LỖI khi migrate (không phải lỗi kết nối) - xem thông báo ngay phía trên." >&2
        exit 1
    fi

    if [ "$attempt" -ge 10 ]; then
        echo "$out" | grep -vE '^#[0-9]+ ' | tail -5 >&2
        echo "[toyland] LỖI: không kết nối được CSDL sau $attempt lần thử." >&2
        echo "[toyland]      Kiểm tra DB_HOST/DB_PORT/DB_USERNAME/DB_PASSWORD và dịch vụ Aiven còn bật." >&2
        exit 1
    fi
    echo "[toyland] CSDL chưa sẵn sàng, thử lại sau 6 giây (lần $attempt)..."
    attempt=$((attempt + 1))
    sleep 6
done

# --- Dữ liệu mẫu: chỉ nạp khi CSDL còn trống -----------------------
php artisan toyland:deploy-seed

# Các lệnh artisan ở trên chạy bằng root; trả quyền lại cho Apache (www-data)
chown -R www-data:www-data storage bootstrap/cache public/uploads

echo "[toyland] Sẵn sàng, chuyển quyền cho Apache."
exec "$@"
