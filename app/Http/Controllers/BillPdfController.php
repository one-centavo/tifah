<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Bill;
use Illuminate\Http\Response;

class BillPdfController extends Controller
{
    /**
     * Generate or download the PDF invoice for a bill.
     */
    public function download(Bill $bill): Response
    {
        $bill->load(['customer', 'details.lot.medicine', 'creator']);

        return response()->view('pdf.invoice', [
            'bill' => $bill,
        ])->header('Content-Type', 'text/html');
    }
}
