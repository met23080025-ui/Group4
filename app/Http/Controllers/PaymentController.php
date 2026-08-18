<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Không đặt trong namespace Gym\ vì controller này phục vụ cả 2 phía:
 * Staff/Owner (store/index — quản lý thanh toán trong Gym) và Member
 * (mine/show — xem thanh toán của chính mình). PaymentPolicy phân định
 * đúng quyền theo từng action, không phụ thuộc route nào gọi tới.
 */
class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    /**
     * Staff/Owner khởi tạo thanh toán cho 1 membership đang pending.
     */
    public function store(Membership $membership): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $existing = $membership->payments()
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PAID])
            ->first();

        if ($existing) {
            return redirect()->route('gym.payments.show', $existing);
        }

        try {
            $payment = $this->paymentService->create($membership);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('gym.payments.show', $payment)
            ->with('success', "Đã tạo thanh toán {$payment->transaction_code}. Đưa QR cho hội viên để chuyển khoản.");
    }

    /**
     * Staff/Owner: danh sách thanh toán đang chờ của Gym mình.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->where('status', Payment::STATUS_PENDING)
            ->with(['member.user', 'membership.package'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('gym.payments.index', ['payments' => $payments]);
    }

    /**
     * Member: danh sách thanh toán của chính mình. Xử lý an toàn khi tài
     * khoản Member tự đăng ký chưa có hồ sơ Member (Khối 5, Ngày 1).
     */
    public function mine(): View
    {
        $member = auth()->user()->member;

        $payments = $member
            ? Payment::query()->where('member_id', $member->id)->with('membership.package')->orderByDesc('created_at')->paginate(15)
            : Payment::query()->whereRaw('1 = 0')->paginate(15);

        return view('member.payments.index', ['payments' => $payments]);
    }

    /**
     * Dùng chung cho cả 2 phía — PaymentPolicy::view() đã phân định đúng
     * quyền theo từng trường hợp.
     */
    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['member.user', 'membership.package', 'gym', 'invoice', 'confirmedBy']);

        return view('payments.show', ['payment' => $payment]);
    }

    /**
     * Staff/Owner xác nhận đã nhận tiền (Khối 2). PaymentPolicy::update() đã
     * loại trừ role member và chặn cross-gym; route model binding (global
     * scope BelongsToGym) đã trả 404 trước đó nếu payment thuộc Gym khác.
     */
    public function confirm(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $payment);

        $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        try {
            $this->paymentService->confirm($payment, $request->user(), $request->input('note'));
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('gym.payments.show', $payment)
            ->with('success', "Đã xác nhận thanh toán {$payment->transaction_code}. Membership đã kích hoạt, hóa đơn đã phát hành.");
    }
}
