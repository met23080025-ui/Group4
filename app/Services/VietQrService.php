<?php

namespace App\Services;

/**
 * Sinh URL ảnh VietQR qua API "quick link" chính thức của img.vietqr.io.
 *
 * Lựa chọn này thay vì tự sinh chuỗi EMVCo (TLV + checksum CRC16-CCITT) rồi
 * vẽ QR bằng simplesoftwareio/simple-qrcode, vì:
 * - Tự implement EMVCo/VietQR là việc mã hóa giao thức (tag-length-value từng
 *   trường, tính CRC16 thủ công) — rất dễ sai lệch tinh vi (sai độ dài field,
 *   sai byte order khi tính checksum) mà khó tự kiểm chứng nếu không có ứng
 *   dụng ngân hàng thật để quét thử.
 * - img.vietqr.io là dịch vụ ảnh do chính VietQR/NAPAS vận hành, đảm bảo
 *   chuỗi EMVCo bên trong ảnh luôn đúng chuẩn và quét được bằng mọi app ngân
 *   hàng hỗ trợ VietQR — ta chỉ cần build đúng URL, không cần tự đảm bảo
 *   tính đúng đắn của thuật toán mã hóa QR.
 * - Không cần gọi HTTP từ backend: ảnh được trình duyệt của người dùng tải
 *   trực tiếp từ img.vietqr.io khi render view, server GymHub chỉ build URL.
 */
class VietQrService
{
    /**
     * Dynamic QR: đã nhúng sẵn số tiền + nội dung chuyển khoản (transaction_code).
     * Dùng cho từng Payment cụ thể — khách quét là điền sẵn đúng số tiền.
     */
    public function dynamicUrl(string $amount, string $addInfo): string
    {
        return $this->baseUrl().'?'.http_build_query([
            'amount' => (string) (int) round((float) $amount),
            'addInfo' => $addInfo,
            'accountName' => config('services.vietqr.account_name'),
        ]);
    }

    /**
     * Static QR: chỉ chứa thông tin tài khoản, không có số tiền/nội dung cố
     * định — dùng để hiển thị "thông tin chuyển khoản chung" của Gym.
     */
    public function staticUrl(): string
    {
        return $this->baseUrl().'?'.http_build_query([
            'accountName' => config('services.vietqr.account_name'),
        ]);
    }

    public function isConfigured(): bool
    {
        return filled(config('services.vietqr.bank_bin')) && filled(config('services.vietqr.account_no'));
    }

    private function baseUrl(): string
    {
        $bin = config('services.vietqr.bank_bin');
        $accountNo = config('services.vietqr.account_no');
        $template = config('services.vietqr.template', 'compact2');

        return "https://img.vietqr.io/image/{$bin}-{$accountNo}-{$template}.png";
    }
}
