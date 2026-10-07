<?php

namespace App\Services\Orders;

use RuntimeException;

/**
 * Lỗi nghiệp vụ khi chuyển trạng thái đơn (sai bước, sai vai trò...).
 * Thông điệp viết sẵn bằng tiếng Việt để hiện thẳng cho người dùng.
 */
class WorkflowException extends RuntimeException
{
}
