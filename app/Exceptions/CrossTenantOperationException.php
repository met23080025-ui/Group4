<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Ném khi 1 Service phát hiện thao tác trộn dữ liệu giữa 2 Gym khác nhau
 * (vd: tạo membership cho member Gym A bằng package thuộc Gym B). Đây là lớp
 * phòng vệ thứ 2 ở tầng Service — lớp 1 là validate trong Form Request
 * (Rule::exists(...)->where('gym_id', ...)), phòng trường hợp Service được
 * gọi trực tiếp từ nơi khác trong tương lai mà bỏ qua Form Request.
 */
class CrossTenantOperationException extends RuntimeException
{
    //
}
