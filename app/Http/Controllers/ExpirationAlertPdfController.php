<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ExpirationAlertService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExpirationAlertPdfController extends Controller
{
    /**
     * Generate and stream/download the physical warehouse picking and marking guide.
     */
    public function download(Request $request, ExpirationAlertService $service): Response
    {
        $urgencyFilter = $request->query('urgency');
        $search = $request->query('search');

        $lots = $service->getExpiringLotsQuery(90, $urgencyFilter, $search)->get();
        $metrics = $service->getAlertSummaryMetrics();

        return response()->view('pdf.expiration-guide', [
            'lots' => $lots,
            'metrics' => $metrics,
            'urgencyFilter' => $urgencyFilter,
            'generatedAt' => Carbon::now(),
            'generatedBy' => auth()->user(),
        ])->header('Content-Type', 'text/html');
    }
}
