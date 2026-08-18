<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Không đặt trong namespace Gym\ vì phục vụ cả 2 phía (Staff/Owner và Member),
 * cùng cách tổ chức với PaymentController — InvoicePolicy phân định quyền.
 */
class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    public function download(Invoice $invoice): StreamedResponse
    {
        $this->authorize('view', $invoice);

        $path = $this->invoiceService->ensureStored($invoice);

        return Storage::disk('local')->download($path, "{$invoice->invoice_number}.pdf");
    }
}
